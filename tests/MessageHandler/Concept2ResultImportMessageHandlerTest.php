<?php

declare(strict_types=1);

/*
 * Copyright 2020 Mathieu Piot
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Tests\MessageHandler;

use App\Entity\User;
use App\Enum\SportType;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Message\Concept2ResultImportMessage;
use App\MessageHandler\Concept2ResultImportMessageHandler;
use App\Repository\TrainingRepository;
use App\Repository\UserRepository;
use App\Service\Concept2\Concept2ApiConsumer;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;

class Concept2ResultImportMessageHandlerTest extends KernelTestCase
{
    public function testAnUnknownUserIsIgnored(): void
    {
        $handler = $this->createHandler();

        $handler(new Concept2ResultImportMessage(0, 42));

        TrainingFactory::assert()->empty();
    }

    public function testAnAlreadyImportedResultIsNotImportedTwice(): void
    {
        $user = $this->createConnectedUser();
        TrainingFactory::createOne(['user' => $user, 'concept2Id' => 42]);
        $handler = $this->createHandler();

        $handler(new Concept2ResultImportMessage($user->getId(), 42));

        TrainingFactory::assert()->count(1, ['concept2Id' => 42]);
    }

    #[DataProvider('provideUnusableStoredTokens')]
    public function testAnUnusableStoredTokenThrowsRecoverable(?string $accessToken, ?\DateTimeImmutable $expiresAt): void
    {
        $user = $this->createConnectedUser($accessToken, $expiresAt);
        $handler = $this->createHandler();

        $this->expectException(RecoverableMessageHandlingException::class);
        $handler(new Concept2ResultImportMessage($user->getId(), 42));
    }

    public function testARateLimitedResultIsRetriedAfterTheRetryAfterDelay(): void
    {
        $user = $this->createConnectedUser();
        $handler = $this->createHandler([new MockResponse('', ['http_code' => 429, 'response_headers' => ['retry-after' => '30']])]);

        $exception = null;
        try {
            $handler(new Concept2ResultImportMessage($user->getId(), 42));
        } catch (RecoverableMessageHandlingException $exception) {
        }

        self::assertInstanceOf(RecoverableMessageHandlingException::class, $exception);
        self::assertSame(30000, $exception->getRetryDelay());
    }

    public function testANewResultIsImportedAsATraining(): void
    {
        $user = $this->createConnectedUser();
        $handler = $this->createHandler([new JsonMockResponse(['data' => [
            'id' => 42,
            'date' => '2020-01-01 10:00:00',
            'time' => 6000,
            'distance' => 2000,
            'stroke_rate' => 24,
            'heart_rate' => ['average' => 150, 'max' => 175],
            'drag_factor' => 88,
            'stroke_count' => 324,
            'stroke_data' => false,
        ]])]);

        $handler(new Concept2ResultImportMessage($user->getId(), 42));

        TrainingFactory::assert()->exists([
            'concept2Id' => 42,
            'user' => $user,
            'sport' => SportType::Ergometer,
            'trainedAt' => new \DateTime('2020-01-01 10:00:00'),
            'duration' => 6000,
            'distance' => 2000,
            'strokeRate' => 24,
            'averageHeartRate' => 150,
            'maxHeartRate' => 175,
            'dragFactor' => 88,
            'strokeCount' => 324,
        ]);
    }

    #[DataProvider('provideMissingMeasures')]
    public function testMissingMeasuresAreImportedAsNull(array $fields): void
    {
        $user = $this->createConnectedUser();
        $handler = $this->createHandler([new JsonMockResponse(['data' => [
            'id' => 42,
            'date' => '2020-01-01 10:00:00',
            'time' => 6000,
            'distance' => 2000,
            'stroke_rate' => 24,
            'stroke_data' => false,
            ...$fields,
        ]])]);

        $handler(new Concept2ResultImportMessage($user->getId(), 42));

        $training = TrainingFactory::find(['concept2Id' => 42]);
        self::assertNull($training->getAverageHeartRate());
        self::assertNull($training->getMaxHeartRate());
        self::assertNull($training->getDragFactor());
        self::assertNull($training->getStrokeCount());
    }

    private function createConnectedUser(?string $accessToken = 'stored-jwt', ?\DateTimeImmutable $expiresAt = new \DateTimeImmutable('+2 hours')): User
    {
        return UserFactory::createOne([
            'concept2RefreshToken' => 'old-refresh',
            'concept2AccessToken' => $accessToken,
            'concept2AccessTokenExpiresAt' => $expiresAt,
        ]);
    }

    /**
     * @param MockResponse[] $responses
     */
    private function createHandler(array $responses = []): Concept2ResultImportMessageHandler
    {
        $consumer = new Concept2ApiConsumer(
            $this->createStub(ClientRegistry::class),
            self::getContainer()->get('doctrine'),
            new MockHttpClient($responses),
            self::getContainer()->get(TrainingRepository::class),
        );

        return new Concept2ResultImportMessageHandler(
            self::getContainer()->get('doctrine')->getManager(),
            $consumer,
            self::getContainer()->get(UserRepository::class),
            self::getContainer()->get(TrainingRepository::class),
        );
    }

    public static function provideUnusableStoredTokens(): \Generator
    {
        yield 'expired' => ['stored-jwt', new \DateTimeImmutable('-1 hour')];
        yield 'never stored' => [null, null];
    }

    public static function provideMissingMeasures(): \Generator
    {
        yield 'absent' => [[]];
        yield 'null' => [['heart_rate' => null, 'drag_factor' => null, 'stroke_count' => null]];
        yield 'null heart rate values' => [['heart_rate' => ['average' => null, 'max' => null]]];
        yield 'zero' => [['heart_rate' => ['average' => 0, 'min' => 0, 'max' => 0], 'drag_factor' => 0, 'stroke_count' => 0]];
        yield 'zero heart rate without max' => [['heart_rate' => ['average' => 0, 'min' => 0]]];
    }
}
