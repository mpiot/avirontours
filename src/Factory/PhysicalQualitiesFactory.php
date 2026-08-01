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
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<PhysicalQualities>
 */
final class PhysicalQualitiesFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
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

    #[\Override]
    protected function initialize(): static
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->afterInstantiate(function(PhysicalQualities $physicalQualities) {})
    }

    public static function class(): string
    {
        return PhysicalQualities::class;
    }
}
