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
use App\Entity\Shell;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::onFlush)]
readonly class ShellMileageUpdater
{
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();
        $shellMetadata = $em->getClassMetadata(Shell::class);

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if (!$entity instanceof LogbookEntry) {
                continue;
            }

            if (null === $coveredDistance = $entity->getCoveredDistance()) {
                continue;
            }

            $shell = $entity->getShell();
            $shell->addToMileage($coveredDistance);
            $uow->recomputeSingleEntityChangeSet($shellMetadata, $shell);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if (!$entity instanceof LogbookEntry) {
                continue;
            }

            $changeSet = $uow->getEntityChangeSet($entity);
            if (false === \array_key_exists('coveredDistance', $changeSet) && false === \array_key_exists('shell', $changeSet)) {
                continue;
            }

            $oldShell = $newShell = $entity->getShell();
            if (\array_key_exists('shell', $changeSet)) {
                $oldShell = $changeSet['shell'][0];
                $newShell = $changeSet['shell'][1];
            }

            $oldCoveredDistance = $newCoveredDistance = $entity->getCoveredDistance();
            if (\array_key_exists('coveredDistance', $changeSet)) {
                $oldCoveredDistance = $changeSet['coveredDistance'][0];
                $newCoveredDistance = $changeSet['coveredDistance'][1];
            }

            if (!$oldShell instanceof Shell || !$newShell instanceof Shell) {
                continue;
            }

            $oldShell->removeToMileage($oldCoveredDistance ?? 0);
            $newShell->addToMileage($newCoveredDistance ?? 0);

            $uow->recomputeSingleEntityChangeSet($shellMetadata, $oldShell);
            $uow->recomputeSingleEntityChangeSet($shellMetadata, $newShell);
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if (!$entity instanceof LogbookEntry) {
                continue;
            }

            // A deleted entity is skipped by computeChangeSets(), so its in-memory values may never have
            // been persisted: give the shell back exactly what it was debited.
            $originalData = $uow->getOriginalEntityData($entity);

            if (false === \array_key_exists('coveredDistance', $originalData) || null === $originalData['coveredDistance']) {
                continue;
            }

            $shell = $originalData['shell'] ?? $entity->getShell();
            if (!$shell instanceof Shell || $uow->isScheduledForDelete($shell)) {
                continue;
            }

            $shell->removeToMileage($originalData['coveredDistance']);
            $uow->recomputeSingleEntityChangeSet($shellMetadata, $shell);
        }
    }
}
