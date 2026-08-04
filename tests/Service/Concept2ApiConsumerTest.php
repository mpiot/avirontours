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

namespace App\Tests\Service;

use App\Entity\Training;
use App\Enum\SportType;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Repository\TrainingRepository;
use App\Service\Concept2ApiConsumer;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class Concept2ApiConsumerTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testGetTrainingsImportsOnlyTheFirstPage(): void
    {
        $user = UserFactory::createOne(['concept2RefreshToken' => 'old-refresh']);
        $httpClient = new MockHttpClient([
            $this->resultsMockResponse([$this->resultFixture(1), $this->resultFixture(2)], totalPages: 3),
        ]);

        $trainings = $this->createConsumer($httpClient)->getTrainings($user, null);

        self::assertCount(2, $trainings);
        self::assertSame([1, 2], array_map(static fn (Training $training): ?int => $training->getConcept2Id(), $trainings));
    }

    public function testGetTrainingsSkipsAlreadyImportedResults(): void
    {
        $user = UserFactory::createOne(['concept2RefreshToken' => 'old-refresh']);
        TrainingFactory::createOne(['user' => $user, 'concept2Id' => 1000]);

        $httpClient = new MockHttpClient([
            $this->resultsMockResponse([$this->resultFixture(1000), $this->resultFixture(2000)], totalPages: 1),
        ]);

        $trainings = $this->createConsumer($httpClient)->getTrainings($user, null);

        self::assertCount(1, $trainings);
        self::assertSame(2000, $trainings[0]->getConcept2Id());
    }

    public function testGetTrainingsMapsErgometerResult(): void
    {
        $user = UserFactory::createOne(['concept2RefreshToken' => 'old-refresh']);
        $httpClient = new MockHttpClient([
            $this->resultsMockResponse([[
                'id' => 42,
                'date' => '2020-03-15 08:30:00',
                'time' => 12000,
                'distance' => 5000,
                'stroke_rate' => 22,
                'heart_rate' => ['average' => 145, 'max' => 168],
                'stroke_data' => false,
            ]], totalPages: 1),
        ]);

        $trainings = $this->createConsumer($httpClient)->getTrainings($user, null);

        self::assertCount(1, $trainings);
        self::assertSame(42, $trainings[0]->getConcept2Id());
        self::assertSame(SportType::Ergometer, $trainings[0]->getSport());
        self::assertSame('2020-03-15', $trainings[0]->getTrainedAt()->format('Y-m-d'));
        self::assertSame(12000, $trainings[0]->getDuration());
        self::assertSame(5000, $trainings[0]->getDistance());
        self::assertSame(22, $trainings[0]->getStrokeRate());
        self::assertSame(145, $trainings[0]->getAverageHeartRate());
        self::assertSame(168, $trainings[0]->getMaxHeartRate());
        self::assertSame(0.5, $trainings[0]->getFeeling());
    }

    public function testStoresRotatedRefreshToken(): void
    {
        $user = UserFactory::createOne(['concept2RefreshToken' => 'old-refresh']);
        $httpClient = new MockHttpClient([$this->resultsMockResponse([], totalPages: 1)]);

        $this->createConsumer($httpClient)->getTrainings($user, null);

        self::assertSame('rotated-refresh', $user->getConcept2RefreshToken());
    }

    public function testKeepsExistingRefreshTokenWhenProviderReturnsNone(): void
    {
        $user = UserFactory::createOne(['concept2RefreshToken' => 'old-refresh']);
        $httpClient = new MockHttpClient([$this->resultsMockResponse([], totalPages: 1)]);

        $this->createConsumer($httpClient, newRefreshToken: null)->getTrainings($user, null);

        self::assertSame('old-refresh', $user->getConcept2RefreshToken());
    }

    public function testGetTrainingsAsksToRetryOnRateLimit(): void
    {
        $user = UserFactory::createOne(['concept2RefreshToken' => 'old-refresh']);
        $httpClient = new MockHttpClient([
            new MockResponse('', ['http_code' => 429, 'response_headers' => ['retry-after' => '30']]),
        ]);

        $exception = null;
        try {
            $this->createConsumer($httpClient)->getTrainings($user, null);
        } catch (RecoverableMessageHandlingException $exception) {
        }

        self::assertInstanceOf(RecoverableMessageHandlingException::class, $exception);
        self::assertSame(30000, $exception->getRetryDelay());
    }

    public function testGetTrainingsDisconnectsAccountWhenRefreshTokenIsRejected(): void
    {
        $user = UserFactory::createOne(['concept2RefreshToken' => 'revoked-refresh']);

        $oauthClient = $this->createStub(OAuth2Client::class);
        $oauthClient->method('refreshAccessToken')->willThrowException(new IdentityProviderException('invalid_grant', 400, ''));
        $clientRegistry = $this->createStub(ClientRegistry::class);
        $clientRegistry->method('getClient')->willReturn($oauthClient);

        try {
            $this->buildConsumer($clientRegistry, new MockHttpClient())->getTrainings($user, null);
            self::fail('Expected an UnrecoverableMessageHandlingException.');
        } catch (UnrecoverableMessageHandlingException) {
        }

        self::assertNull($user->getConcept2RefreshToken());
    }

    private function createConsumer(MockHttpClient $httpClient, ?string $newRefreshToken = 'rotated-refresh'): Concept2ApiConsumer
    {
        $options = ['access_token' => 'access-token'];
        if (null !== $newRefreshToken) {
            $options['refresh_token'] = $newRefreshToken;
        }

        $oauthClient = $this->createStub(OAuth2Client::class);
        $oauthClient->method('refreshAccessToken')->willReturn(new AccessToken($options));

        $clientRegistry = $this->createStub(ClientRegistry::class);
        $clientRegistry->method('getClient')->willReturn($oauthClient);

        return $this->buildConsumer($clientRegistry, $httpClient);
    }

    private function buildConsumer(ClientRegistry $clientRegistry, MockHttpClient $httpClient): Concept2ApiConsumer
    {
        return new Concept2ApiConsumer(
            $clientRegistry,
            self::getContainer()->get('doctrine'),
            $httpClient,
            self::getContainer()->get(TrainingRepository::class),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function resultFixture(int $id): array
    {
        return [
            'id' => $id,
            'date' => '2020-01-01 10:00:00',
            'time' => 6000,
            'distance' => 2000,
            'stroke_rate' => 24,
            'heart_rate' => ['average' => 150, 'max' => 175],
            'stroke_data' => false,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $results
     */
    private function resultsMockResponse(array $results, int $totalPages): JsonMockResponse
    {
        return new JsonMockResponse([
            'data' => $results,
            'meta' => ['pagination' => ['total_pages' => $totalPages]],
        ]);
    }
}
