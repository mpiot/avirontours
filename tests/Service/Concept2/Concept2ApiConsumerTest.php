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

namespace App\Tests\Service\Concept2;

use App\Entity\User;
use App\Enum\SportType;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Repository\TrainingRepository;
use App\Service\Concept2\Concept2ApiConsumer;
use App\Service\Concept2\Exception\Concept2AccountRevokedException;
use App\Service\Concept2\Exception\Concept2Exception;
use App\Service\Concept2\Exception\Concept2RateLimitedException;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\JsonMockResponse;
use Symfony\Component\HttpClient\Response\MockResponse;

class Concept2ApiConsumerTest extends KernelTestCase
{
    public function testGetAccessTokenReusesAStoredTokenExpiringInMoreThanAnHour(): void
    {
        $user = $this->createConnectedUser();
        $consumer = $this->createConsumer();

        $consumer->getAccessToken($user);

        self::assertSame('stored-jwt', $user->getConcept2AccessToken());
    }

    #[DataProvider('provideUnusableStoredTokens')]
    public function testGetAccessTokenRefreshesAnUnusableStoredToken(?string $accessToken, ?\DateTimeImmutable $expiresAt): void
    {
        $user = $this->createConnectedUser($accessToken, $expiresAt);
        $tokenExpiresAt = new \DateTimeImmutable('+14 days');
        $consumer = $this->createConsumer(tokenExpiresAt: $tokenExpiresAt);

        $consumer->getAccessToken($user);

        self::assertSame('refreshed-jwt', $user->getConcept2AccessToken());
        self::assertSame($tokenExpiresAt->getTimestamp(), $user->getConcept2AccessTokenExpiresAt()->getTimestamp());
        self::assertSame('rotated-refresh', $user->getConcept2RefreshToken());
    }

    public function testGetAccessTokenKeepsTheRefreshTokenWhenTheProviderDoesNotRotateIt(): void
    {
        $user = $this->createConnectedUser(accessToken: null, expiresAt: null);
        $consumer = $this->createConsumer(newRefreshToken: null);

        $consumer->getAccessToken($user);

        self::assertSame('old-refresh', $user->getConcept2RefreshToken());
    }

    public function testGetAccessTokenRejectsATokenResponseWithoutExpiry(): void
    {
        $user = $this->createConnectedUser(accessToken: null, expiresAt: null);
        $consumer = $this->createConsumer(tokenExpiresAt: null);

        $exception = null;
        try {
            $consumer->getAccessToken($user);
        } catch (Concept2Exception $exception) {
        }

        self::assertInstanceOf(Concept2Exception::class, $exception);
        self::assertNull($user->getConcept2AccessToken());
        self::assertNull($user->getConcept2AccessTokenExpiresAt());
    }

    public function testGetAccessTokenDisconnectsTheAccountWhenTheRefreshTokenIsRejected(): void
    {
        $user = $this->createConnectedUser(expiresAt: new \DateTimeImmutable('-1 hour'));
        $oauthClient = $this->createStub(OAuth2Client::class);
        $oauthClient->method('refreshAccessToken')->willThrowException(new IdentityProviderException('invalid_grant', 400, ''));
        $clientRegistry = $this->createStub(ClientRegistry::class);
        $clientRegistry->method('getClient')->willReturn($oauthClient);
        $consumer = $this->buildConsumer($clientRegistry);

        $exception = null;
        try {
            $consumer->getAccessToken($user);
        } catch (Concept2AccountRevokedException $exception) {
        }

        self::assertInstanceOf(Concept2AccountRevokedException::class, $exception);
        self::assertNull($user->getConcept2RefreshToken());
        self::assertNull($user->getConcept2AccessToken());
        self::assertNull($user->getConcept2AccessTokenExpiresAt());
    }

    public function testGetNewResultIdsListsOnlyTheFirstPage(): void
    {
        $user = $this->createConnectedUser();
        $consumer = $this->createConsumer($this->resultsMockResponse([$this->resultFixture(1), $this->resultFixture(2)], totalPages: 3));

        $resultIds = $consumer->getNewResultIds($user, null);

        self::assertSame([1, 2], $resultIds);
    }

    public function testGetNewResultIdsSkipsAlreadyImportedResults(): void
    {
        $user = $this->createConnectedUser();
        TrainingFactory::createOne(['user' => $user, 'concept2Id' => 1000]);
        $consumer = $this->createConsumer($this->resultsMockResponse([$this->resultFixture(1000), $this->resultFixture(2000)], totalPages: 1));

        $resultIds = $consumer->getNewResultIds($user, null);

        self::assertSame([2000], $resultIds);
    }

    public function testGetNewResultIdsRequestsTheResultsApiUrl(): void
    {
        $user = $this->createConnectedUser();
        $response = $this->resultsMockResponse([], totalPages: 1);
        $consumer = $this->createConsumer($response);

        $consumer->getNewResultIds($user, new \DateTimeImmutable('2024-06-01 10:00:00', new \DateTimeZone('Europe/Paris')));

        self::assertSame('https://log.concept2.com/api/users/me/results?type=rower&number=250&updated_after=2024-06-01%2008:00:00', $response->getRequestUrl());
    }

    public function testGetNewResultIdsThrowsRateLimitedWithTheRetryAfterDelay(): void
    {
        $user = $this->createConnectedUser();
        $consumer = $this->createConsumer(new MockResponse('', ['http_code' => 429, 'response_headers' => ['retry-after' => '30']]));

        $exception = null;
        try {
            $consumer->getNewResultIds($user, null);
        } catch (Concept2RateLimitedException $exception) {
        }

        self::assertInstanceOf(Concept2RateLimitedException::class, $exception);
        self::assertSame(30, $exception->getRetryAfter());
    }

    public function testGetNewResultIdsThrowsRateLimitedWithADefaultDelayWhenTheHeaderIsMissing(): void
    {
        $user = $this->createConnectedUser();
        $consumer = $this->createConsumer(new MockResponse('', ['http_code' => 429]));

        $exception = null;
        try {
            $consumer->getNewResultIds($user, null);
        } catch (Concept2RateLimitedException $exception) {
        }

        self::assertInstanceOf(Concept2RateLimitedException::class, $exception);
        self::assertSame(60, $exception->getRetryAfter());
    }

    public function testGetTrainingMapsTheResultToAnErgometerTraining(): void
    {
        $user = $this->createConnectedUser();
        $consumer = $this->createConsumer($this->resultMockResponse([
            'id' => 42,
            'date' => '2020-03-15 08:30:00',
            'time' => 12000,
            'distance' => 5000,
            'stroke_rate' => 22,
            'heart_rate' => ['average' => 145, 'max' => 168],
            'stroke_data' => false,
        ]));

        $training = $consumer->getTraining($user, 42);

        self::assertSame(42, $training->getConcept2Id());
        self::assertSame(SportType::Ergometer, $training->getSport());
        self::assertSame('2020-03-15', $training->getTrainedAt()->format('Y-m-d'));
        self::assertSame(12000, $training->getDuration());
        self::assertSame(5000, $training->getDistance());
        self::assertSame(22, $training->getStrokeRate());
        self::assertSame(145, $training->getAverageHeartRate());
        self::assertSame(168, $training->getMaxHeartRate());
        self::assertNull($training->getFeeling());
        self::assertNull($training->getRatedPerceivedExertion());
    }

    public function testGetTrainingCreatesAPhaseFromStrokeData(): void
    {
        $user = $this->createConnectedUser();
        $result = $this->resultFixture(42);
        $result['stroke_data'] = true;
        $result['workout'] = ['targets' => [], 'splits' => []];
        $consumer = $this->createConsumer([
            $this->resultMockResponse($result),
            new JsonMockResponse(['data' => [
                ['t' => 0, 'd' => 0, 'p' => 120, 'spm' => 20, 'hr' => 140],
                ['t' => 100, 'd' => 250, 'p' => 118, 'spm' => 22, 'hr' => 150],
            ]]),
        ]);

        $training = $consumer->getTraining($user, 42);

        $phases = $training->getTrainingPhases();
        self::assertCount(1, $phases);
        self::assertSame([0, 100], $phases->first()->getTimes());
        self::assertSame([0, 250], $phases->first()->getDistances());
        self::assertSame([120, 118], $phases->first()->getPaces());
        self::assertSame([20, 22], $phases->first()->getStrokeRates());
        self::assertSame([140, 150], $phases->first()->getHeartRates());
    }

    public function testGetTrainingCreatesOnePhasePerInterval(): void
    {
        $user = $this->createConnectedUser();
        $result = $this->resultFixture(42);
        $result['stroke_data'] = true;
        $result['workout'] = ['intervals' => [
            ['time' => 3000, 'distance' => 1000, 'stroke_rate' => 26, 'heart_rate' => ['average' => 150, 'max' => 160, 'ending' => 158]],
            ['time' => 2900, 'distance' => 1000, 'stroke_rate' => 28, 'heart_rate' => ['average' => 162, 'max' => 171, 'ending' => 170]],
        ]];
        $consumer = $this->createConsumer([
            $this->resultMockResponse($result),
            new JsonMockResponse(['data' => [
                ['t' => 0, 'd' => 0, 'p' => 120, 'spm' => 26, 'hr' => 140],
                ['t' => 100, 'd' => 250, 'p' => 118, 'spm' => 26, 'hr' => 155],
                ['t' => 0, 'd' => 0, 'p' => 115, 'spm' => 28, 'hr' => 160],
                ['t' => 90, 'd' => 260, 'p' => 112, 'spm' => 29, 'hr' => 170],
            ]]),
        ]);

        $training = $consumer->getTraining($user, 42);

        $phases = $training->getTrainingPhases();
        self::assertCount(2, $phases);
        self::assertSame(3000, $phases->first()->getDuration());
        self::assertSame(158, $phases->first()->getEndingHeartRate());
        self::assertSame([0, 100], $phases->first()->getTimes());
        self::assertSame([140, 155], $phases->first()->getHeartRates());
        self::assertSame(2900, $phases->last()->getDuration());
        self::assertSame(170, $phases->last()->getEndingHeartRate());
        self::assertSame([0, 90], $phases->last()->getTimes());
        self::assertSame([160, 170], $phases->last()->getHeartRates());
    }

    public function testGetTrainingUsesTheStoredTokenWithoutRefreshing(): void
    {
        $user = $this->createConnectedUser();
        $response = $this->resultMockResponse($this->resultFixture(7));
        $consumer = $this->createConsumer($response);

        $consumer->getTraining($user, 7);

        self::assertSame('Authorization: Bearer stored-jwt', $response->getRequestOptions()['normalized_headers']['authorization'][0]);
    }

    public function testGetTrainingThrowsWhenTheLogbookAnswersAnError(): void
    {
        $user = $this->createConnectedUser();
        $consumer = $this->createConsumer(new MockResponse('', ['http_code' => 500]));

        $this->expectException(Concept2Exception::class);

        $consumer->getTraining($user, 42);
    }

    #[DataProvider('provideUnreadableAnswers')]
    public function testGetTrainingThrowsWhenTheLogbookAnswerIsUnreadable(MockResponse $response): void
    {
        $user = $this->createConnectedUser();
        $consumer = $this->createConsumer($response);

        $this->expectException(Concept2Exception::class);

        $consumer->getTraining($user, 42);
    }

    private function createConnectedUser(?string $accessToken = 'stored-jwt', ?\DateTimeImmutable $expiresAt = new \DateTimeImmutable('+2 hours')): User
    {
        return UserFactory::createOne([
            'concept2RefreshToken' => 'old-refresh',
            'concept2AccessToken' => $accessToken,
            'concept2AccessTokenExpiresAt' => $expiresAt,
        ]);
    }

    private function createConsumer(MockResponse|callable|array $responses = [], ?string $newRefreshToken = 'rotated-refresh', ?\DateTimeImmutable $tokenExpiresAt = new \DateTimeImmutable('+14 days')): Concept2ApiConsumer
    {
        $options = ['access_token' => 'refreshed-jwt'];
        if (null !== $newRefreshToken) {
            $options['refresh_token'] = $newRefreshToken;
        }
        if (null !== $tokenExpiresAt) {
            $options['expires'] = $tokenExpiresAt->getTimestamp();
        }

        $oauthClient = $this->createStub(OAuth2Client::class);
        $oauthClient->method('refreshAccessToken')->willReturn(new AccessToken($options));

        $clientRegistry = $this->createStub(ClientRegistry::class);
        $clientRegistry->method('getClient')->willReturn($oauthClient);

        return $this->buildConsumer($clientRegistry, $responses);
    }

    private function buildConsumer(ClientRegistry $clientRegistry, MockResponse|callable|array $responses = []): Concept2ApiConsumer
    {
        return new Concept2ApiConsumer(
            $clientRegistry,
            self::getContainer()->get('doctrine'),
            new MockHttpClient($responses),
            self::getContainer()->get(TrainingRepository::class),
        );
    }

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

    private function resultsMockResponse(array $results, int $totalPages): JsonMockResponse
    {
        return new JsonMockResponse([
            'data' => $results,
            'meta' => ['pagination' => ['total_pages' => $totalPages]],
        ]);
    }

    private function resultMockResponse(array $result): JsonMockResponse
    {
        return new JsonMockResponse(['data' => $result]);
    }

    public static function provideUnusableStoredTokens(): \Generator
    {
        yield 'expired' => ['stored-jwt', new \DateTimeImmutable('-1 hour')];
        yield 'expiring within the hour' => ['stored-jwt', new \DateTimeImmutable('+30 minutes')];
        yield 'never stored' => [null, null];
    }

    public static function provideUnreadableAnswers(): \Generator
    {
        yield 'not JSON' => [new MockResponse('<html>Maintenance</html>')];
        yield 'body cut in transfer' => [new MockResponse((static function (): \Generator {
            yield '{"data":';

            throw new \RuntimeException('Connection reset by peer');
        })())];
    }
}
