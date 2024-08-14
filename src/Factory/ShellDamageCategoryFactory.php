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

use App\Entity\ShellDamageCategory;
use App\Repository\ShellDamageCategoryRepository;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<ShellDamageCategory>
 *
 * @method        ShellDamageCategory|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static ShellDamageCategory|Proxy                                createOne(array $attributes = [])
 * @method static ShellDamageCategory|Proxy                                find(object|array|mixed $criteria)
 * @method static ShellDamageCategory|Proxy                                findOrCreate(array $attributes)
 * @method static ShellDamageCategory|Proxy                                first(string $sortedField = 'id')
 * @method static ShellDamageCategory|Proxy                                last(string $sortedField = 'id')
 * @method static ShellDamageCategory|Proxy                                random(array $attributes = [])
 * @method static ShellDamageCategory|Proxy                                randomOrCreate(array $attributes = [])
 * @method static ShellDamageCategoryRepository|ProxyRepositoryDecorator   repository()
 * @method static ShellDamageCategory[]|Proxy[]                            all()
 * @method static ShellDamageCategory[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static ShellDamageCategory[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static ShellDamageCategory[]|Proxy[]                            findBy(array $attributes)
 * @method static ShellDamageCategory[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static ShellDamageCategory[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        ShellDamageCategory&Proxy<ShellDamageCategory> create(array|callable $attributes = [])
 * @phpstan-method static ShellDamageCategory&Proxy<ShellDamageCategory> createOne(array $attributes = [])
 * @phpstan-method static ShellDamageCategory&Proxy<ShellDamageCategory> find(object|array|mixed $criteria)
 * @phpstan-method static ShellDamageCategory&Proxy<ShellDamageCategory> findOrCreate(array $attributes)
 * @phpstan-method static ShellDamageCategory&Proxy<ShellDamageCategory> first(string $sortedField = 'id')
 * @phpstan-method static ShellDamageCategory&Proxy<ShellDamageCategory> last(string $sortedField = 'id')
 * @phpstan-method static ShellDamageCategory&Proxy<ShellDamageCategory> random(array $attributes = [])
 * @phpstan-method static ShellDamageCategory&Proxy<ShellDamageCategory> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<ShellDamageCategory, EntityRepository> repository()
 * @phpstan-method static list<ShellDamageCategory&Proxy<ShellDamageCategory>> all()
 * @phpstan-method static list<ShellDamageCategory&Proxy<ShellDamageCategory>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<ShellDamageCategory&Proxy<ShellDamageCategory>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<ShellDamageCategory&Proxy<ShellDamageCategory>> findBy(array $attributes)
 * @phpstan-method static list<ShellDamageCategory&Proxy<ShellDamageCategory>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<ShellDamageCategory&Proxy<ShellDamageCategory>> randomSet(int $number, array $attributes = [])
 */
final class ShellDamageCategoryFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->name(),
            'priority' => self::faker()->randomElement(ShellDamageCategory::getAvailablePriorities()),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->beforeInstantiate(function(ShellDamageCategory $shellDamageCategory) {})
    }

    public static function class(): string
    {
        return ShellDamageCategory::class;
    }
}
