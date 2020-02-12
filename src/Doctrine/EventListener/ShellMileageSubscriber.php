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

namespace App\Doctrine\EventListener;

use App\Entity\LogbookEntry;
use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

class ShellMileageSubscriber implements EventSubscriber
{
    private $oldCoveredDistance;

    public function getSubscribedEvents()
    {
        return [
            Events::preUpdate,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof LogbookEntry) {
            return;
        }

        $this->oldCoveredDistance = $args->getOldValue('coveredDistance');
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $this->updateShellMileage('update', $args);
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $this->updateShellMileage('remove', $args);
    }

    private function updateShellMileage(string $action, LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if (!$entity instanceof LogbookEntry) {
            return;
        }

        $coveredDistance = $entity->getCoveredDistance();

        switch ($action) {
            case 'update':
                if (null !== $coveredDistance && $this->oldCoveredDistance !== $coveredDistance) {
                    $entity->getShell()->addToMileage($coveredDistance - $this->oldCoveredDistance);
                }
                break;
            case 'remove':
                $entity->getShell()->removeToMileage($coveredDistance);
                break;
        }

        $args->getObjectManager()->flush();
    }
}
