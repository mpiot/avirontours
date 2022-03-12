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
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Token\AccessToken;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Concept2ApiConsumer
{
    public const API_URL = 'https://log.concept2.com/api';

    public function __construct(private ClientRegistry $clientRegistry, private ManagerRegistry $managerRegistry, private HttpClientInterface $httpClient)
    {
    }

    public function getTrainings(User $user, ?\DateTimeInterface $startAt): array
    {
        $accessToken = $this->getAccessToken($user);
        $results = $this->getResults($accessToken, $startAt);
        $trainings = [];

        foreach ($results as $key => $result) {
            $trainings[] = $this->createTraining($accessToken, $user, $result);
        }

        return $trainings;
    }

    private function createTraining(AccessToken $accessToken, User $user, array $result): Training
    {
        $training = new Training($user);
        $training
            ->setSport(Training::SPORT_ERGOMETER)
            ->setType(Training::TYPE_B1)
            ->setTrainedAt(new \DateTime($result['date']))
            ->setDuration($result['time'] / 10)
            ->setDistance($result['distance'] / 1000)
        ;

        if (false === $result['stroke_data']) {
            return $training;
        }

        // Retrieve the stroke data to create phases
        $strokeData = $this->getStrokeData($accessToken, $result['id']);

        // If there is no interval, or only one, create it
        if (false === \array_key_exists('intervals', $result['workout']) || 1 === \count($result['workout']['intervals'])) {
            $trainingPhase = $this->createTrainingPhase(
                $result['time'],
                $result['distance'],
                $strokeData[0]
            );

            $training->addTrainingPhase($trainingPhase);

            return $training;
        }

        // Else, create many phases, and split the strokeData in the number of phases
        foreach ($result['workout']['intervals'] as $key => $interval) {
            $trainingPhase = $this->createTrainingPhase(
                $interval['time'],
                $interval['distance'],
                $strokeData[$key]
            );

            $training->addTrainingPhase($trainingPhase);
        }

        return $training;
    }

    private function createTrainingPhase(int $duration, int $distance, array $strokeData): TrainingPhase
    {
        $trainingPhase = new TrainingPhase();
        $trainingPhase
            ->setDuration($duration)
            ->setDistance($distance)
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

    private function getResults(AccessToken $accessToken, ?\DateTimeInterface $startAt): array
    {
        $query = ['type' => 'rower'];
        if (null !== $startAt) {
            $query['from'] = $startAt->format('Y-m-d');
        }

        $response = $this->httpClient->request('GET', sprintf('%s/users/me/results', self::API_URL), [
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

    private function getStrokeData(AccessToken $accessToken, int $resultIdentifier): array
    {
        $response = $this->httpClient->request('GET', sprintf('%s/users/me/results/%s/strokes', self::API_URL, $resultIdentifier), [
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
        foreach ($response->toArray()['data'] as $datum) {
            if ($maxTime > $datum['t']) {
                ++$phaseKey;
            }

            $strokeData[$phaseKey]['times'][] = $datum['t'];
            $strokeData[$phaseKey]['distances'][] = $datum['d'];
            $strokeData[$phaseKey]['paces'][] = $datum['p'] > 2400 ? 2400 : $datum['p'];
            $strokeData[$phaseKey]['strokeRates'][] = $datum['spm'] > 100 ? 100 : $datum['spm'];
            $strokeData[$phaseKey]['heartRates'][] = $datum['hr'] > 300 ? 300 : $datum['hr'];

            $maxTime = $datum['t'];
        }

        return $strokeData;
    }

    private function getAccessToken(User $user): AccessToken
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
