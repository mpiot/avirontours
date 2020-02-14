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

use App\Entity\ShellDamage;
use App\Entity\ShellDamageCategory;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class ShellDamageListener
{
    private $enableShell = false;

    public function prePersist(ShellDamage $shellDamage, LifecycleEventArgs $args)
    {
        if (ShellDamageCategory::PRIORITY_HIGH === $shellDamage->getCategory()->getPriority()) {
            $shellDamage->getShell()->setAvailable(false);
        }
    }

    public function preUpdate(ShellDamage $shellDamage, PreUpdateEventArgs $args)
    {
        if ($args->hasChangedField('repairAt') &&
            null === $args->getOldValue('repairAt') &&
            $args->getNewValue('repairAt') instanceof \DateTime
        ) {
            $this->enableShell = true;
        }
    }

    public function postUpdate(ShellDamage $shellDamage, LifecycleEventArgs $args)
    {
        if (true === $this->enableShell) {
            $shellDamage->getShell()->setAvailable(true);
            $args->getObjectManager()->flush();
        }
    }
}
