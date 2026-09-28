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

namespace App\Service\Concept2;

use App\Entity\Training;
use App\Entity\TrainingPhase;
use App\Entity\User;
use App\Enum\SportType;
use App\Repository\TrainingRepository;
use App\Service\Concept2\Exception\Concept2AccountRevokedException;
use App\Service\Concept2\Exception\Concept2Exception;
use App\Service\Concept2\Exception\Concept2RateLimitedException;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Concept2ApiConsumer
{
    public const string API_URL = 'https://log.concept2.com/api';
    private const int HTTP_TIMEOUT = 15;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly ManagerRegistry $managerRegistry,
        private readonly HttpClientInterface $httpClient,
        private readonly TrainingRepository $trainingRepository,
    ) {
    }

    public function getAccessToken(User $user): string
    {
        $storedToken = $user->getConcept2AccessToken();
        $expiresAt = $user->getConcept2AccessTokenExpiresAt();
        // One hour of margin: the workers never refresh, the queued imports must fit in it
        if (null !== $storedToken && null !== $expiresAt && $expiresAt > new \DateTimeImmutable('+1 hour')) {
            return $storedToken;
        }

        /** @var OAuth2Client $client */
        $client = $this->clientRegistry->getClient('concept2');

        try {
            $accessToken = $client->refreshAccessToken($user->getConcept2RefreshToken());
        } catch (IdentityProviderException $e) {
            $user
                ->setConcept2RefreshToken(null)
                ->setConcept2AccessToken(null)
                ->setConcept2AccessTokenExpiresAt(null)
            ;
            $this->managerRegistry->getManager()->flush();

            throw new Concept2AccountRevokedException('The Concept2 account must be reconnected.', previous: $e);
        }

        if (null === $accessToken->getExpires()) {
            throw new Concept2Exception('The Concept2 token response carries no expiry.');
        }

        $user
            // Keep the current refresh token when the provider does not rotate it
            ->setConcept2RefreshToken($accessToken->getRefreshToken() ?? $user->getConcept2RefreshToken())
            ->setConcept2AccessToken($accessToken->getToken())
            ->setConcept2AccessTokenExpiresAt((new \DateTimeImmutable())->setTimestamp($accessToken->getExpires()))
        ;
        $this->managerRegistry->getManager()->flush();

        return $accessToken->getToken();
    }

    /**
     * @return int[]
     */
    public function getNewResultIds(User $user, ?\DateTimeImmutable $updatedAfter): array
    {
        $query = ['type' => 'rower', 'number' => 250];
        if (null !== $updatedAfter) {
            $query['updated_after'] = $updatedAfter->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }

        // Sync only the first page, all pages are too many results to sync
        $url = \sprintf('%s/users/me/results', self::API_URL);

        $resultIds = [];
        foreach ($this->requestData($user, $url, $query) as $result) {
            if ($this->trainingRepository->isConcept2ResultImported($user, $result['id'])) {
                continue;
            }

            $resultIds[] = $result['id'];
        }

        return $resultIds;
    }

    public function getTraining(User $user, int $resultId): Training
    {
        $url = \sprintf('%s/users/me/results/%s', self::API_URL, $resultId);

        return $this->createTraining($user, $this->requestData($user, $url));
    }

    /**
     * @return array<array{
     *     t: int[],
     *     d: int[],
     *     p: int[],
     *     spm: int[],
     *     hr: int[],
     * }>
     */
    public function getFormattedStrokeData(User $user, int $resultIdentifier): array
    {
        $strokes = $this->getStrokeData($user, $resultIdentifier);

        $phaseKey = 0;
        $maxTime = 0;
        $formattedStrokes = [];
        foreach ($strokes as $stroke) {
            if ($maxTime > $stroke['t']) {
                ++$phaseKey;
            }

            $formattedStrokes[$phaseKey]['t'][] = $stroke['t'];
            $formattedStrokes[$phaseKey]['d'][] = $stroke['d'];
            $formattedStrokes[$phaseKey]['p'][] = min($stroke['p'], 2400);
            $formattedStrokes[$phaseKey]['spm'][] = min($stroke['spm'], 70);
            $formattedStrokes[$phaseKey]['hr'][] = min($stroke['hr'], 300);

            $maxTime = $stroke['t'];
        }

        return $formattedStrokes;
    }

    private function createTraining(User $user, array $result): Training
    {
        $averageHeartRate = $result['heart_rate']['average'] ?? null;
        $maxHeartRate = $result['heart_rate']['max'] ?? null;

        $training = new Training($user);
        $training
            ->setConcept2Id($result['id'])
            ->setSport(SportType::Ergometer)
            ->setTrainedAt(new \DateTime($result['date']))
            ->setDuration($result['time'])
            ->setDistance($result['distance'])
            ->setStrokeRate($result['stroke_rate'])
            ->setAverageHeartRate(0 !== $averageHeartRate ? $averageHeartRate : null)
            ->setMaxHeartRate(0 !== $maxHeartRate ? $maxHeartRate : null)
        ;

        if (false === $result['stroke_data']) {
            return $training;
        }

        // Retrieve the stroke data to create phases
        $strokeData = $this->getFormattedStrokeData($user, $result['id']);

        // If there is no interval, or only one, create it
        // Validate stroke data count
        if (
            false === \array_key_exists('intervals', $result['workout'])
            || 1 === \count($result['workout']['intervals'])
        ) {
            $trainingPhase = $this->createTrainingPhaseFromFormattedStrokes(
                $result,
                $strokeData[0] ?? null
            );

            $training->addTrainingPhase($trainingPhase);

            return $training;
        }

        // Else, create many phases, and split the strokeData in the number of phases
        // Check the number of intervals match the number of stroke data
        foreach ($result['workout']['intervals'] as $key => $intervalData) {
            $trainingPhase = $this->createTrainingPhaseFromFormattedStrokes(
                $intervalData,
                $strokeData[$key] ?? null
            );

            $training->addTrainingPhase($trainingPhase);
        }

        return $training;
    }

    private function createTrainingPhaseFromFormattedStrokes(
        array $intervalData,
        ?array $strokeData,
    ): TrainingPhase {
        $trainingPhase = new TrainingPhase();
        $trainingPhase
            ->setDuration($intervalData['time'])
            ->setDistance($intervalData['distance'])
            ->setStrokeRate($intervalData['stroke_rate'])
            ->setAverageHeartRate($intervalData['heart_rate']['average'] ?? null)
            ->setMaxHeartRate($intervalData['heart_rate']['max'] ?? null)
            ->setEndingHeartRate($intervalData['heart_rate']['ending'] ?? null)
        ;

        if (null === $strokeData) {
            return $trainingPhase;
        }

        $trainingPhase
            ->setTimes($strokeData['t'] ?? null)
            ->setDistances($strokeData['d'] ?? null)
            ->setPaces($strokeData['p'] ?? null)
            ->setStrokeRates($strokeData['spm'] ?? null)
        ;

        if (
            1 !== \count(array_unique($strokeData['hr']))
            || 0 !== array_unique($strokeData['hr'])[0]
        ) {
            $trainingPhase->setHeartRates($strokeData['hr']);
        }

        return $trainingPhase;
    }

    /**
     * @return array<array{
     *     d: int,
     *     p: int,
     *     hr: int,
     *     spm: int,
     *     t: int,
     * }>
     */
    private function getStrokeData(User $user, int $resultIdentifier): array
    {
        $url = \sprintf('%s/users/me/results/%s/strokes', self::API_URL, $resultIdentifier);

        return $this->requestData($user, $url);
    }

    private function requestData(User $user, string $url, array $query = []): array
    {
        $response = $this->httpClient->request('GET', $url, [
            'query' => $query,
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth_bearer' => (string) $user->getConcept2AccessToken(),
            'timeout' => self::HTTP_TIMEOUT,
        ]);

        try {
            if (Response::HTTP_TOO_MANY_REQUESTS === $response->getStatusCode()) {
                $retryAfter = $response->getHeaders(false)['retry-after'][0] ?? null;

                throw new Concept2RateLimitedException(is_numeric($retryAfter) ? (int) $retryAfter : null);
            }

            return $response->toArray()['data'];
        } catch (HttpClientExceptionInterface $e) {
            throw new Concept2Exception('The Concept2 Logbook did not answer successfully.', previous: $e);
        }
    }
}
