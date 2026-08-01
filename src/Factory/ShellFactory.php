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

use App\Entity\Shell;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Shell>
 */
final class ShellFactory extends PersistentObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->name(),
            'numberRowers' => self::faker()->randomElement([1, 2, 4, 8]),
            'coxed' => self::faker()->boolean(0.1),
            'rowingType' => self::faker()->randomElement(Shell::getAvailableRowingTypes()),
            'yolette' => self::faker()->boolean(0.1),
            'productionYear' => self::faker()->year(),
            'weightCategory' => self::faker()->randomElement(Shell::getAvailableWeightCategories()),
            'riggerMaterial' => self::faker()->randomElement(Shell::getAvailableRiggerMaterials()),
            'riggerPosition' => self::faker()->randomElement(Shell::getAvailableRiggerPositions()),
            'enabled' => true,
            'personalBoat' => self::faker()->boolean(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->beforeInstantiate(function(Shell $shell) {})
    }

    public static function class(): string
    {
        return Shell::class;
    }
}
