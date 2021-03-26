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
use App\Repository\ShellRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static      Shell|Proxy createOne(array $attributes = [])
 * @method static      Shell[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static      Shell|Proxy findOrCreate(array $attributes)
 * @method static      Shell|Proxy random(array $attributes = [])
 * @method static      Shell|Proxy randomOrCreate(array $attributes = [])
 * @method static      Shell[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static      Shell[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static      ShellRepository|RepositoryProxy repository()
 * @method Shell|Proxy create($attributes = [])
 */
final class ShellFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->name,
            'numberRowers' => self::faker()->randomElement([1, 2, 4, 8]),
            'coxed' => self::faker()->boolean(0.1),
            'rowerCategory' => self::faker()->randomElement(Shell::getAvailableRowerCategories()),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->beforeInstantiate(function(Shell $shell) {})
        ;
    }

    protected static function getClass(): string
    {
        return Shell::class;
    }
}
