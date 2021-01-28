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

use App\Entity\ShellDamageCategory;
use App\Repository\ShellDamageCategoryRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static                    ShellDamageCategory|Proxy createOne(array $attributes = [])
 * @method static                    ShellDamageCategory[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static                    ShellDamageCategory|Proxy findOrCreate(array $attributes)
 * @method static                    ShellDamageCategory|Proxy random(array $attributes = [])
 * @method static                    ShellDamageCategory|Proxy randomOrCreate(array $attributes = [])
 * @method static                    ShellDamageCategory[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static                    ShellDamageCategory[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static                    ShellDamageCategoryRepository|RepositoryProxy repository()
 * @method ShellDamageCategory|Proxy create($attributes = [])
 */
final class ShellDamageCategoryFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->name,
            'priority' => self::faker()->randomElement(ShellDamageCategory::getAvailablePriorities()),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->beforeInstantiate(function(ShellDamageCategory $shellDamageCategory) {})
        ;
    }

    protected static function getClass(): string
    {
        return ShellDamageCategory::class;
    }
}
