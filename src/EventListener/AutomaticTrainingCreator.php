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
use App\Util\DurationManipulator;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;

class AutomaticTrainingCreator
{
    private $trainings = [];

    public function prePersist(LogbookEntry $logbookEntry): void
    {
        if (null === $logbookEntry->getStartAt() || null === $logbookEntry->getEndAt()) {
            return;
        }

        $this->createTrainings($logbookEntry);
    }

    public function preUpdate(LogbookEntry $logbookEntry, PreUpdateEventArgs $args): void
    {
        if (false === $args->hasChangedField('endAt') || null !== $args->getOldValue('endAt') || null === $args->getNewValue('endAt')) {
            return;
        }

        $this->createTrainings($logbookEntry);
    }

    public function postUpdate(LogbookEntry $logbookEntry, LifecycleEventArgs $args): void
    {
        if (empty($this->trainings)) {
            return;
        }

        foreach ($this->trainings as $training) {
            $args->getObjectManager()->persist($training);
        }

        $args->getObjectManager()->flush();
    }

    private function createTrainings(LogbookEntry $logbookEntry): void
    {
        $duration = DurationManipulator::dateIntervalToSeconds($logbookEntry->getStartAt()->diff($logbookEntry->getEndAt()));

        foreach ($logbookEntry->getCrewMembers() as $user) {
            if (false === $user->getAutomaticTraining()) {
                continue;
            }

            $training = new Training($user);
            $training
                ->setTrainedAt($logbookEntry->getStartAt())
                ->setDuration($duration)
                ->setDistance($logbookEntry->getCoveredDistance())
                ->setSport(Training::SPORT_ROWING)
                ->setType(Training::TYPE_B1)
            ;

            $this->trainings[] = $training;
        }
    }
}
