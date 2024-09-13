<?php

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
use App\Enum\TrainingType;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Concept2ApiConsumer
{
    public const API_URL = 'https://log.concept2.com/api';

    public function __construct(private readonly ClientRegistry $clientRegistry, private readonly ManagerRegistry $managerRegistry, private readonly HttpClientInterface $httpClient)
    {
    }

    public function getTrainings(User $user, ?\DateTimeInterface $startAt): array
    {
        $accessToken = $this->getAccessToken($user);
        $results = $this->getResults($accessToken, $startAt);
        $trainings = [];

        foreach ($results as $result) {
            $trainings[] = $this->createTraining($accessToken, $user, $result);
        }

        return $trainings;
    }

    private function createTraining(AccessTokenInterface $accessToken, User $user, array $result): Training
    {
        $averageHeartRate = $result['heart_rate']['average'] ?? null;
        $maxHeartRate = $result['heart_rate']['max'] ?? null;

        $training = new Training($user);
        $training
            ->setSport(SportType::Ergometer)
            ->setType(TrainingType::B1)
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
        $strokeData = $this->getStrokeData($accessToken, $result['id']);

        // If there is no interval, or only one, create it
        if (false === \array_key_exists('intervals', $result['workout']) || 1 === \count($result['workout']['intervals'])) {
            $trainingPhase = $this->createTrainingPhase(
                $result,
                $strokeData[0]
            );

            $training->addTrainingPhase($trainingPhase);

            return $training;
        }

        // Else, create many phases, and split the strokeData in the number of phases
        foreach ($result['workout']['intervals'] as $key => $intervalData) {
            $trainingPhase = $this->createTrainingPhase(
                $intervalData,
                $strokeData[$key]
            );

            $training->addTrainingPhase($trainingPhase);
        }

        return $training;
    }

    private function createTrainingPhase(
        array $intervalData,
        array $strokeData,
    ): TrainingPhase {
        $trainingPhase = new TrainingPhase();
        $trainingPhase
            ->setDuration($intervalData['time'])
            ->setDistance($intervalData['distance'])
            ->setStrokeRate($intervalData['stroke_rate'])
            ->setAverageHeartRate($intervalData['heart_rate']['average'] ?? null)
            ->setMaxHeartRate($intervalData['heart_rate']['max'] ?? null)
            ->setEndingHeartRate($intervalData['heart_rate']['ending'] ?? null)
            ->setTimes($strokeData['times'])
            ->setDistances($strokeData['distances'])
            ->setPaces($strokeData['paces'])
            ->setStrokeRates($strokeData['strokeRates'])
        ;

        if (1 !== \count(array_unique($strokeData['heartRates'])) || 0 !== array_unique($strokeData['heartRates'])[0]) {
            $trainingPhase->setHeartRates($strokeData['heartRates']);
        }

        return $trainingPhase;
    }

    private function getResults(AccessTokenInterface $accessToken, ?\DateTimeInterface $startAt): array
    {
        $query = ['type' => 'rower'];
        if (null !== $startAt) {
            $query['from'] = $startAt->format('Y-m-d H:i:s');
        }

        $response = $this->httpClient->request('GET', \sprintf('%s/users/me/results', self::API_URL), [
            'query' => $query,
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth_bearer' => $accessToken->getToken(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new \Exception('The Logbook Api do not return successfully response.');
        }

        return $response->toArray()['data'];
    }

    private function getStrokeData(AccessTokenInterface $accessToken, int $resultIdentifier): array
    {
        $response = $this->httpClient->request('GET', \sprintf('%s/users/me/results/%s/strokes', self::API_URL, $resultIdentifier), [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'auth_bearer' => $accessToken->getToken(),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new \Exception('The Logbook Api do not return successfully response.');
        }

        $phaseKey = 0;
        $maxTime = 0;
        $strokeData = [];
        foreach ($response->toArray()['data'] as $datum) {
            if ($maxTime > $datum['t']) {
                ++$phaseKey;
            }

            $strokeData[$phaseKey]['times'][] = $datum['t'];
            $strokeData[$phaseKey]['distances'][] = $datum['d'];
            $strokeData[$phaseKey]['paces'][] = min($datum['p'], 2400);
            $strokeData[$phaseKey]['strokeRates'][] = min($datum['spm'], 70);
            $strokeData[$phaseKey]['heartRates'][] = min($datum['hr'], 300);

            $maxTime = $datum['t'];
        }

        return $strokeData;
    }

    private function getAccessToken(User $user): AccessTokenInterface
    {
        /** @var OAuth2Client $client */
        $client = $this->clientRegistry->getClient('concept2');

        // Get an access token from the refreshToken
        $accessToken = $client->refreshAccessToken($user->getConcept2RefreshToken());

        // Persist the new Refresh token
        $user->setConcept2RefreshToken($accessToken->getRefreshToken());
        $this->managerRegistry->getManager()->flush();

        return $accessToken;
    }
}
