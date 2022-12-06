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

namespace App\MessageHandler;

use App\Message\Concept2ImportMessage;
use App\Repository\UserRepository;
use App\Service\Concept2ApiConsumer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class Concept2ImportMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Concept2ApiConsumer $apiConsumer,
        private readonly UserRepository $userRepository
    ) {
    }

    public function __invoke(Concept2ImportMessage $message)
    {
        $user = $this->userRepository->find($message->getUserId());
        if (null === $user) {
            return;
        }

        $trainings = $this->apiConsumer->getTrainings($user, $user->getConcept2LastImportAt());
        foreach ($trainings as $training) {
            $this->entityManager->persist($training);
        }
        $user->setConcept2LastImportAt(new \DateTimeImmutable());
        $this->entityManager->flush();
    }
}
