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

use App\Entity\ShellDamage;
use App\Repository\ShellDamageRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static                ShellDamage|Proxy findOrCreate(array $attributes)
 * @method static                ShellDamage|Proxy random()
 * @method static                ShellDamage[]|Proxy[] randomSet(int $number)
 * @method static                ShellDamage[]|Proxy[] randomRange(int $min, int $max)
 * @method static                ShellDamageRepository|RepositoryProxy repository()
 * @method ShellDamage|Proxy     create($attributes = [])
 * @method ShellDamage[]|Proxy[] createMany(int $number, $attributes = [])
 */
final class ShellDamageFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'category' => ShellDamageCategoryFactory::new()->create(),
            'shell' => ShellFactory::new()->create(),
            'description' => self::faker()->text,
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->beforeInstantiate(function(ShellDamage $shellDamage) {})
        ;
    }

    protected static function getClass(): string
    {
        return ShellDamage::class;
    }
}
