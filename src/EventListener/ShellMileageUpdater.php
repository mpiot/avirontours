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

namespace App\EventListener;

use App\Entity\LogbookEntry;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ShellMileageUpdater
{
    public function prePersist(LogbookEntry $logbookEntry, LifecycleEventArgs $args)
    {
        if (null === $coveredDistance = $logbookEntry->getCoveredDistance()) {
            return;
        }

        $logbookEntry->getShell()->addToMileage($coveredDistance);
    }

    public function preUpdate(LogbookEntry $logbookEntry, PreUpdateEventArgs $args)
    {
        if (false === $args->hasChangedField('coveredDistance') && false === $args->hasChangedField('shell')) {
            return;
        }

        // Define old and new distance
        if (true === $args->hasChangedField('coveredDistance')) {
            $oldDistance = $args->getOldValue('coveredDistance');
            $newDistance = $args->getNewValue('coveredDistance');
        } else {
            $oldDistance = $logbookEntry->getCoveredDistance();
            $newDistance = $logbookEntry->getCoveredDistance();
        }

        // Update shells
        if (true === $args->hasChangedField('shell')) {
            $oldShell = $args->getOldValue('shell');
            $newShell = $args->getNewValue('shell');

            $oldShell->removeToMileage($oldDistance ?? 0);
            $newShell->addToMileage($newDistance ?? 0);
        } else {
            $logbookEntry->getShell()
                ->removeToMileage($oldDistance ?? 0)
                ->addToMileage($newDistance ?? 0)
            ;
        }
    }

    public function preRemove(LogbookEntry $logbookEntry, LifecycleEventArgs $args)
    {
        if (null === $coveredDistance = $logbookEntry->getCoveredDistance()) {
            return;
        }

        $logbookEntry->getShell()->removeToMileage($coveredDistance);
    }
}
