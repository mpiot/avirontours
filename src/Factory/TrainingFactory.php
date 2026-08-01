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

namespace App\Factory;

use App\Entity\Training;
use App\Enum\SportType;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Training>
 */
final class TrainingFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new(),
            'trainedAt' => self::faker()->dateTimeThisYear(),
            'duration' => self::faker()->numberBetween(12000, 72000),
            'distance' => self::faker()->numberBetween(8000, 20000),
            'sport' => self::faker()->randomElement(SportType::cases()),
            'feeling' => self::faker()->randomFloat(1, 0, 1),
            'comment' => self::faker()->optional()->text(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->afterInstantiate(function(Training $training) {})
    }

    public static function class(): string
    {
        return Training::class;
    }
}
