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
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<Shell>
 *
 * @method        Shell|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static Shell|Proxy                                createOne(array $attributes = [])
 * @method static Shell|Proxy                                find(object|array|mixed $criteria)
 * @method static Shell|Proxy                                findOrCreate(array $attributes)
 * @method static Shell|Proxy                                first(string $sortedField = 'id')
 * @method static Shell|Proxy                                last(string $sortedField = 'id')
 * @method static Shell|Proxy                                random(array $attributes = [])
 * @method static Shell|Proxy                                randomOrCreate(array $attributes = [])
 * @method static ShellRepository|ProxyRepositoryDecorator   repository()
 * @method static Shell[]|Proxy[]                            all()
 * @method static Shell[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static Shell[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static Shell[]|Proxy[]                            findBy(array $attributes)
 * @method static Shell[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static Shell[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Shell&Proxy<Shell> create(array|callable $attributes = [])
 * @phpstan-method static Shell&Proxy<Shell> createOne(array $attributes = [])
 * @phpstan-method static Shell&Proxy<Shell> find(object|array|mixed $criteria)
 * @phpstan-method static Shell&Proxy<Shell> findOrCreate(array $attributes)
 * @phpstan-method static Shell&Proxy<Shell> first(string $sortedField = 'id')
 * @phpstan-method static Shell&Proxy<Shell> last(string $sortedField = 'id')
 * @phpstan-method static Shell&Proxy<Shell> random(array $attributes = [])
 * @phpstan-method static Shell&Proxy<Shell> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<Shell, EntityRepository> repository()
 * @phpstan-method static list<Shell&Proxy<Shell>> all()
 * @phpstan-method static list<Shell&Proxy<Shell>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Shell&Proxy<Shell>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Shell&Proxy<Shell>> findBy(array $attributes)
 * @phpstan-method static list<Shell&Proxy<Shell>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Shell&Proxy<Shell>> randomSet(int $number, array $attributes = [])
 */
final class ShellFactory extends PersistentProxyObjectFactory
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

    protected function initialize(): self
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
