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

namespace App\Service;

use App\Entity\Training;
use App\Entity\TrainingPhase;
use App\Entity\User;
use App\Enum\SportType;
use App\Repository\TrainingRepository;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class Concept2ApiConsumer
{
    public const string API_URL = 'https://log.concept2.com/api';

    private const int HTTP_TIMEOUT = 15;

    private const int DEFAULT_RETRY_AFTER = 60;

    public function __construct(
        private readonly ClientRegistry $clientRegistry,
        private readonly ManagerRegistry $managerRegistry,
        private readonly HttpClientInterface $httpClient,
        private readonly TrainingRepository $trainingRepository,
    ) {
    }

    public function getTrainings(User $user, ?\DateTimeInterface $startAt): array
    {
        $accessToken = $this->getAccessToken($user);
        $results = $this->getResults($accessToken, $startAt);

        $trainings = [];
        foreach ($results as $result) {
            if ($this->trainingRepository->isConcept2ResultImported($user, $result['id'])) {
                continue;
            }

            $trainings[] = $this->createTraining($accessToken, $user, $result);
        }

        return $trainings;
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
    public function getFormattedStrokeData(AccessTokenInterface $accessToken, int $resultIdentifier): array
    {
        $strokes = $this->getStrokeData($accessToken, $resultIdentifier);

        $phaseKey = 0;
        $maxTime = 0;
        $formatedStrokes = [];
        foreach ($strokes as $stroke) {
            if ($maxTime > $stroke['t']) {
                ++$phaseKey;
            }

            $formatedStrokes[$phaseKey]['t'][] = $stroke['t'];
            $formatedStrokes[$phaseKey]['d'][] = $stroke['d'];
            $formatedStrokes[$phaseKey]['p'][] = min($stroke['p'], 2400);
            $formatedStrokes[$phaseKey]['spm'][] = min($stroke['spm'], 70);
            $formatedStrokes[$phaseKey]['hr'][] = min($stroke['hr'], 300);

            $maxTime = $stroke['t'];
        }

        return $formatedStrokes;
    }

    private function createTraining(AccessTokenInterface $accessToken, User $user, array $result): Training
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
            ->setFeeling(5)
            ->setStrokeRate($result['stroke_rate'])
            ->setAverageHeartRate(0 !== $averageHeartRate ? $averageHeartRate : null)
            ->setMaxHeartRate(0 !== $maxHeartRate ? $maxHeartRate : null)
        ;

        if (false === $result['stroke_data']) {
            return $training;
        }

        // Retrieve the stroke data to create phases
        $strokeData = $this->getFormattedStrokeData($accessToken, $result['id']);

        // If there is no interval, or only one, create it
        // Validate stroke data count
        if (
            false === \array_key_exists('intervals', $result['workout'])
            || 1 === \count($result['workout']['intervals'])
        ) {
            $trainingPhase = $this->createTrainingPhaseFromFormatedStrokes(
                $result,
                $strokeData[0] ?? null
            );

            $training->addTrainingPhase($trainingPhase);

            return $training;
        }

        // Else, create many phases, and split the strokeData in the number of phases
        // Check the number of intervals match the number of stroke data
        foreach ($result['workout']['intervals'] as $key => $intervalData) {
            $trainingPhase = $this->createTrainingPhaseFromFormatedStrokes(
                $intervalData,
                $strokeData[$key] ?? null
            );

            $training->addTrainingPhase($trainingPhase);
        }

        return $training;
    }

    private function createTrainingPhaseFromFormatedStrokes(
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
     *     id: int,
     *     user_id: int,
     *     date: string,
     *     timezone: ?string,
     *     date_utc: ?string,
     *     distance: int,
     *     type: string,
     *     time: int,
     *     time_formatted: string,
     *     workout_type: string,
     *     source: string,
     *     weight_class: string,
     *     verified: bool,
     *     ranked: bool,
     *     comments: ?string,
     *     privacy: string,
     *     stroke_data: bool,
     *     calories_total: int,
     *     drag_factor: int,
     *     stroke_count: int,
     *     stroke_rate: int,
     *     heart_rate: array{
     *         min: int,
     *         average: int,
     *         max: int,
     *         ending: int,
     *     },
     *     workout: array{
     *         targets: array,
     *         splits: array{
     *             time: int,
     *             distance: int,
     *             calories_total: int,
     *             wattminutes_total: int,
     *             stroke_rate: int,
     *             heart_rate: array{
     *                 min: int,
     *                 average: int,
     *                 max: int,
     *                 ending: int,
     *              },
     *         },
     *     },
     *     real_time: null
     * }>
     */
    private function getResults(AccessTokenInterface $accessToken, ?\DateTimeInterface $startAt): array
    {
        $query = ['type' => 'rower'];
        if (null !== $startAt) {
            $query['from'] = $startAt->format('Y-m-d H:i:s');
        }

        // Sync only the first page, all page are too many results to sync
        $response = $this->httpClient->request('GET', \sprintf('%s/users/me/results', self::API_URL), [
            'query' => $query,
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth_bearer' => $accessToken->getToken(),
            'timeout' => self::HTTP_TIMEOUT,
        ]);

        $this->guardResponse($response);

        return $response->toArray()['data'];
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
    private function getStrokeData(AccessTokenInterface $accessToken, int $resultIdentifier): array
    {
        $response = $this->httpClient->request('GET', \sprintf('%s/users/me/results/%s/strokes', self::API_URL, $resultIdentifier), [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth_bearer' => $accessToken->getToken(),
            'timeout' => self::HTTP_TIMEOUT,
        ]);

        $this->guardResponse($response);

        return $response->toArray()['data'];
    }

    private function getAccessToken(User $user): AccessTokenInterface
    {
        /** @var OAuth2Client $client */
        $client = $this->clientRegistry->getClient('concept2');

        // Get an access token from the refreshToken
        try {
            $accessToken = $client->refreshAccessToken($user->getConcept2RefreshToken());
        } catch (IdentityProviderException $e) {
            $user->setConcept2RefreshToken(null);
            $this->managerRegistry->getManager()->flush();

            throw new UnrecoverableMessageHandlingException('The Concept2 account must be reconnected.', previous: $e);
        }

        // Update the refresh token
        $refreshToken = $accessToken->getRefreshToken();
        if (null !== $refreshToken) {
            $user->setConcept2RefreshToken($refreshToken);
            $this->managerRegistry->getManager()->flush();
        }

        return $accessToken;
    }

    private function guardResponse(ResponseInterface $response): void
    {
        $statusCode = $response->getStatusCode();
        if (200 === $statusCode) {
            return;
        }

        if (Response::HTTP_TOO_MANY_REQUESTS === $statusCode) {
            $retryAfter = $response->getHeaders(false)['retry-after'][0] ?? null;
            $delay = is_numeric($retryAfter) ? (int) $retryAfter : self::DEFAULT_RETRY_AFTER;

            throw new RecoverableMessageHandlingException('The Concept2 Logbook rate limit was reached.', retryDelay: $delay * 1000);
        }

        throw new \Exception('The Logbook Api do not return successfully response.');
    }
}
