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
use Doctrine\ORM\Events;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(tags: [
    [
        'doctrine.orm.entity_listener' => [
            'event' => Events::prePersist,
            'entity' => Shell::class,
            'lazy' => true,
        ],
    ],
    [
        'doctrine.orm.entity_listener' => [
            'event' => Events::preUpdate,
            'entity' => Shell::class,
            'lazy' => true,
        ],
    ],
])]
class ShellAbbreviationUpdater
{
    public function __construct(private readonly ShellAbbreviationGenerator $shellAbbreviationGenerator)
    {
    }

    public function prePersist(Shell $shell): void
    {
        $this->generateAbbreviation($shell);
    }

    public function preUpdate(Shell $shell): void
    {
        $this->generateAbbreviation($shell);
    }

    private function generateAbbreviation(Shell $shell): void
    {
        $abbreviation = $this->shellAbbreviationGenerator->generateAbbreviation($shell);
        $shell->setAbbreviation($abbreviation);
    }
}
