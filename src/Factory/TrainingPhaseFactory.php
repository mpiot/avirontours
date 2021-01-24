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

namespace App\Factory;

use App\Entity\TrainingPhase;
use App\Repository\TrainingPhaseRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static              TrainingPhase|Proxy createOne(array $attributes = [])
 * @method static              TrainingPhase[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static              TrainingPhase|Proxy findOrCreate(array $attributes)
 * @method static              TrainingPhase|Proxy random(array $attributes = [])
 * @method static              TrainingPhase|Proxy randomOrCreate(array $attributes = [])
 * @method static              TrainingPhase[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static              TrainingPhase[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static              TrainingPhaseRepository|RepositoryProxy repository()
 * @method TrainingPhase|Proxy create($attributes = [])
 */
final class TrainingPhaseFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->optional()->word,
            'intensity' => self::faker()->randomElement(TrainingPhase::getAvailableIntensities()),
            'duration' => self::faker()->dateTime,
            'distance' => self::faker()->optional()->randomFloat(1, 2, 60),
            'split' => self::faker()->optional()->numerify('#:##.#'),
            'spm' => self::faker()->optional()->numberBetween(14, 45),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->afterInstantiate(function(TrainingPhase $trainingPhase) {})
        ;
    }

    protected static function getClass(): string
    {
        return TrainingPhase::class;
    }
}
