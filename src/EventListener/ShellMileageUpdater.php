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
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class ShellMileageUpdater
{
    private ?Shell $oldShell = null;
    private ?Shell $newShell = null;
    private ?float $oldCoveredDistance = null;
    private ?float $newCoveredDistance = null;

    public function prePersist(LogbookEntry $logbookEntry, LifecycleEventArgs $args): void
    {
        if (null === $coveredDistance = $logbookEntry->getCoveredDistance()) {
            return;
        }

        $logbookEntry->getShell()->addToMileage($coveredDistance);
    }

    public function preUpdate(LogbookEntry $logbookEntry, PreUpdateEventArgs $args): void
    {
        if (false === $args->hasChangedField('coveredDistance') && false === $args->hasChangedField('shell')) {
            return;
        }

        // Set mileages
        $this->oldCoveredDistance = $logbookEntry->getCoveredDistance();
        $this->newCoveredDistance = $logbookEntry->getCoveredDistance();

        if (true === $args->hasChangedField('coveredDistance')) {
            $this->oldCoveredDistance = $args->getOldValue('coveredDistance');
            $this->newCoveredDistance = $args->getNewValue('coveredDistance');
        }

        // Set shells
        $this->newShell = $logbookEntry->getShell();

        if (true === $args->hasChangedField('shell')) {
            $this->oldShell = $args->getOldValue('shell');
            $this->newShell = $args->getNewValue('shell');
        }
    }

    public function postUpdate(LogbookEntry $logbookEntry, LifecycleEventArgs $args): void
    {
        if (null === $this->oldShell && null === $this->newShell) {
            return;
        }

        // Update shells
        if (null !== $this->oldShell) {
            $this->oldShell->removeToMileage($this->oldCoveredDistance ?? 0);
            $this->newShell->addToMileage($this->newCoveredDistance ?? 0);
        } else {
            $this->newShell
                ->removeToMileage($this->oldCoveredDistance ?? 0)
                ->addToMileage($this->newCoveredDistance ?? 0)
            ;
        }

        $args->getObjectManager()->flush();
    }

    public function preRemove(LogbookEntry $logbookEntry, LifecycleEventArgs $args): void
    {
        if (null === $coveredDistance = $logbookEntry->getCoveredDistance()) {
            return;
        }

        $logbookEntry->getShell()->removeToMileage($coveredDistance);
    }
}
