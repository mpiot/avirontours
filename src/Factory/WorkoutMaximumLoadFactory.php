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

use App\Entity\WorkoutMaximumLoad;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<WorkoutMaximumLoad>
 */
final class WorkoutMaximumLoadFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new(),
            'rowingTirage' => self::faker()->numberBetween(20, 200),
            'benchPress' => self::faker()->numberBetween(20, 200),
            'squat' => self::faker()->numberBetween(20, 200),
            'legPress' => self::faker()->numberBetween(20, 200),
            'clean' => self::faker()->numberBetween(20, 200),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->afterInstantiate(function(WorkoutMaximumLoad $workoutMaximumLoad) {})
    }

    public static function class(): string
    {
        return WorkoutMaximumLoad::class;
    }
}
