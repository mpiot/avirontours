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

use App\Entity\PhysicalQualities;
use App\Repository\PhysicalQualitiesRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static                  PhysicalQualities|Proxy createOne(array $attributes = [])
 * @method static                  PhysicalQualities[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static                  PhysicalQualities|Proxy findOrCreate(array $attributes)
 * @method static                  PhysicalQualities|Proxy random(array $attributes = [])
 * @method static                  PhysicalQualities|Proxy randomOrCreate(array $attributes = [])
 * @method static                  PhysicalQualities[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static                  PhysicalQualities[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static                  PhysicalQualitiesRepository|RepositoryProxy repository()
 * @method PhysicalQualities|Proxy create($attributes = [])
 */
final class PhysicalQualitiesFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'user' => UserFactory::new(),
            'proprioception' => self::faker()->numberBetween(0, 20),
            'weightPowerRatio' => self::faker()->numberBetween(0, 20),
            'explosiveStrength' => self::faker()->numberBetween(0, 20),
            'enduranceStrength' => self::faker()->numberBetween(0, 20),
            'maximumStrength' => self::faker()->numberBetween(0, 20),
            'stressResistance' => self::faker()->numberBetween(0, 20),
            'coreStrength' => self::faker()->numberBetween(0, 20),
            'flexibility' => self::faker()->numberBetween(0, 20),
            'recovery' => self::faker()->numberBetween(0, 20),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->afterInstantiate(function(PhysicalQualities $physicalQualities) {})
        ;
    }

    protected static function getClass(): string
    {
        return PhysicalQualities::class;
    }
}
