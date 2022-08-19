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
use App\Repository\WorkoutMaximumLoadRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static WorkoutMaximumLoad|Proxy                     createOne(array $attributes = [])
 * @method static WorkoutMaximumLoad[]|Proxy[]                 createMany(int $number, $attributes = [])
 * @method static WorkoutMaximumLoad|Proxy                     findOrCreate(array $attributes)
 * @method static WorkoutMaximumLoad|Proxy                     random(array $attributes = [])
 * @method static WorkoutMaximumLoad|Proxy                     randomOrCreate(array $attributes = [])
 * @method static WorkoutMaximumLoad[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static WorkoutMaximumLoad[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static WorkoutMaximumLoadRepository|RepositoryProxy repository()
 * @method        WorkoutMaximumLoad|Proxy                     create($attributes = [])
 */
final class WorkoutMaximumLoadFactory extends ModelFactory
{
    protected function getDefaults(): array
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

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->afterInstantiate(function(WorkoutMaximumLoad $workoutMaximumLoad) {})
        ;
    }

    protected static function getClass(): string
    {
        return WorkoutMaximumLoad::class;
    }
}
