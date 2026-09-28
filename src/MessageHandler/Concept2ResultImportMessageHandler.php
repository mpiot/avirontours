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

namespace App\MessageHandler;

use App\Message\Concept2ResultImportMessage;
use App\Repository\TrainingRepository;
use App\Repository\UserRepository;
use App\Service\Concept2\Concept2ApiConsumer;
use App\Service\Concept2\Exception\Concept2RateLimitedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\RecoverableMessageHandlingException;

#[AsMessageHandler]
readonly class Concept2ResultImportMessageHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Concept2ApiConsumer $apiConsumer,
        private UserRepository $userRepository,
        private TrainingRepository $trainingRepository,
    ) {
    }

    public function __invoke(Concept2ResultImportMessage $message): void
    {
        $user = $this->userRepository->find($message->getUserId());
        if (null === $user) {
            return;
        }

        // A retried message may have been delivered already
        if ($this->trainingRepository->isConcept2ResultImported($user, $message->getConcept2ResultId())) {
            return;
        }

        // Workers never refresh the token: parallel refreshes would race the rotation
        $expiresAt = $user->getConcept2AccessTokenExpiresAt();
        if (null === $user->getConcept2AccessToken() || null === $expiresAt || $expiresAt <= new \DateTimeImmutable()) {
            throw new RecoverableMessageHandlingException('The stored Concept2 access token is missing or expired.');
        }

        try {
            $training = $this->apiConsumer->getTraining($user, $message->getConcept2ResultId());
        } catch (Concept2RateLimitedException $e) {
            throw new RecoverableMessageHandlingException($e->getMessage(), previous: $e, retryDelay: $e->getRetryAfter() * 1000);
        }

        $this->entityManager->persist($training);
        $this->entityManager->flush();
    }
}
