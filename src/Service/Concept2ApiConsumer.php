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
use App\Entity\User;
use App\Enum\SportType;
use App\Enum\TrainingSource;
use App\Repository\TrainingRepository;
use App\Service\Fit\Exception\DuplicateFitFileException;
use App\Service\Fit\Exception\FitImportException;
use App\Service\Fit\FitTrainingImporter;
use Doctrine\Persistence\ManagerRegistry;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Client\OAuth2Client;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessTokenInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
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
        private readonly FitTrainingImporter $fitTrainingImporter,
        private readonly TrimpCalculator $trimpCalculator,
        private readonly LoggerInterface $logger,
        private readonly Filesystem $filesystem,
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

            $training = $this->createTraining($accessToken, $user, $result);
            if (null !== $training) {
                $trainings[] = $training;
            }
        }

        return $trainings;
    }

    /**
     * Null when the member already had this session (uploaded as a FIT file before the sync ran).
     */
    private function createTraining(AccessTokenInterface $accessToken, User $user, array $result): ?Training
    {
        // The FIT export only exists for results recorded stroke by stroke; a manual logbook entry
        // keeps the summary mapping below.
        $export = true === $result['stroke_data'] ? $this->downloadFitExport($accessToken, $result['id']) : null;

        if (null !== $export) {
            try {
                $training = $this->fitTrainingImporter->import($user, $export, $result['id']);
                // The listing is filtered on type=rower: whatever sport the export claims, this is an erg.
                $training->setSport(SportType::Ergometer);

                return $training;
            } catch (DuplicateFitFileException $e) {
                // Uploaded by hand earlier: stamp the id so the result is never fetched again.
                $e->getExisting()->setConcept2Id($result['id']);

                return null;
            } catch (FitImportException $e) {
                $this->logger->warning('The Concept2 FIT export is not usable, falling back to the summary.', [
                    'result' => $result['id'],
                    'exception' => $e,
                ]);
            } finally {
                // The import copied it into the private storage, the download itself is over.
                $this->filesystem->remove($export->getPathname());
            }
        }

        return $this->createTrainingFromSummary($user, $result);
    }

    private function createTrainingFromSummary(User $user, array $result): Training
    {
        $averageHeartRate = $result['heart_rate']['average'] ?? null;
        $maxHeartRate = $result['heart_rate']['max'] ?? null;

        $training = new Training($user);
        $training
            ->setSource(TrainingSource::Concept2)
            ->setConcept2Id($result['id'])
            ->setSport(SportType::Ergometer)
            ->setTrainedAt(new \DateTime($result['date']))
            ->setDuration($result['time'])
            ->setDistance($result['distance'])
            ->setStrokeRate($result['stroke_rate'])
            ->setAverageHeartRate(0 !== $averageHeartRate ? $averageHeartRate : null)
            ->setMaxHeartRate(0 !== $maxHeartRate ? $maxHeartRate : null)
        ;
        $training->setTrimp($this->trimpCalculator->compute($training));

        return $training;
    }

    /**
     * The exported file, on a temporary path the caller has to remove; null when the logbook has no
     * FIT export for this result.
     */
    private function downloadFitExport(AccessTokenInterface $accessToken, int $resultIdentifier): ?File
    {
        $response = $this->httpClient->request('GET', \sprintf('%s/users/me/results/%s/export/fit', self::API_URL, $resultIdentifier), [
            'headers' => [
                'Accept' => 'application/octet-stream',
            ],
            'auth_bearer' => $accessToken->getToken(),
            'timeout' => self::HTTP_TIMEOUT,
        ]);

        if (Response::HTTP_NOT_FOUND === $response->getStatusCode()) {
            return null;
        }

        $this->guardResponse($response);

        $path = $this->filesystem->tempnam(sys_get_temp_dir(), "concept2_{$resultIdentifier}_", '.fit');
        $this->filesystem->dumpFile($path, $response->getContent());

        return new File($path);
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
