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

use App\Entity\Training;
use App\Repository\TrainingRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static         Training|Proxy createOne(array $attributes = [])
 * @method static         Training[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static         Training|Proxy findOrCreate(array $attributes)
 * @method static         Training|Proxy random(array $attributes = [])
 * @method static         Training|Proxy randomOrCreate(array $attributes = [])
 * @method static         Training[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static         Training[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static         TrainingRepository|RepositoryProxy repository()
 * @method Training|Proxy create($attributes = [])
 */
final class TrainingFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'user' => UserFactory::createOne(),
            'trainedAt' => self::faker()->dateTime,
            'duration' => new \DateInterval('PT1H10M25S'),
            'distance' => self::faker()->randomFloat(1, 2, 20),
            'sport' => self::faker()->randomElement(Training::getAvailableSports()),
            'feeling' => self::faker()->randomElement(Training::getAvailableFeelings()),
            'comment' => self::faker()->optional()->text,
            'trainingPhases' => TrainingPhaseFactory::new()->many(0, 5),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->afterInstantiate(function(Training $training) {})
        ;
    }

    protected static function getClass(): string
    {
        return Training::class;
    }
}
