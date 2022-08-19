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

use App\Entity\Shell;
use App\Service\ShellAbbreviationGenerator;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('doctrine.event_subscriber')]
class ShellAbbreviationSubscriber implements EventSubscriber
{
    public function __construct(private readonly ShellAbbreviationGenerator $abbreviationGenerator)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->generateAbbreviation($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->generateAbbreviation($args);
    }

    private function generateAbbreviation(LifecycleEventArgs $args): void
    {
        $shell = $args->getObject();

        if (!$shell instanceof Shell) {
            return;
        }

        $shell->setAbbreviation($this->abbreviationGenerator->generateAbbreviation($shell));
    }
}
