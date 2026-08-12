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

namespace App\EventListener;

use App\Entity\LogbookEntry;
use App\Entity\Training;
use App\Enum\SportType;
use App\Util\DurationManipulator;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
readonly class AutomaticTrainingCreator
{
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof LogbookEntry) {
                continue;
            }

            $this->createTrainings($entity, $em);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof LogbookEntry) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);
            if (false === \array_key_exists('endAt', $changeSet)) {
                continue;
            }

            if (null !== $changeSet['endAt'][0] || null === $changeSet['endAt'][1]) {
                continue;
            }

            $this->createTrainings($entity, $em);
        }
    }

    private function createTrainings(LogbookEntry $logbookEntry, EntityManagerInterface $em): void
    {
        $date = $logbookEntry->getDate();
        $startAt = $logbookEntry->getStartAt();
        $endAt = $logbookEntry->getEndAt();

        // Is the session finished ?
        if (null === $date || null === $startAt || null === $endAt) {
            return;
        }

        $duration = DurationManipulator::dateIntervalToTenthSeconds($startAt->diff($endAt));

        $coveredDistance = $logbookEntry->getCoveredDistance();
        $distance = null !== $coveredDistance ? (int) round($coveredDistance * 1000) : null;

        $uow = $em->getUnitOfWork();
        $trainingMetadata = $em->getClassMetadata(Training::class);

        foreach ($logbookEntry->getCrewMembers() as $user) {
            if (false === $user->getAutomaticTraining()) {
                continue;
            }

            $trainedAt = new \DateTime(\sprintf('%s %s', $date->format('Y-m-d'), $startAt->format('H:i:s')));

            $training = new Training($user);
            $training
                ->setTrainedAt($trainedAt)
                ->setSport(SportType::Rowing)
                ->setDuration($duration)
                ->setDistance($distance)
            ;

            $em->persist($training);
            $uow->computeChangeSet($trainingMetadata, $training);
        }
    }
}
