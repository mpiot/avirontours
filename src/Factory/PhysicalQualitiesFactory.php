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
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<PhysicalQualities>
 *
 * @method        PhysicalQualities|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static PhysicalQualities|Proxy                                createOne(array $attributes = [])
 * @method static PhysicalQualities|Proxy                                find(object|array|mixed $criteria)
 * @method static PhysicalQualities|Proxy                                findOrCreate(array $attributes)
 * @method static PhysicalQualities|Proxy                                first(string $sortedField = 'id')
 * @method static PhysicalQualities|Proxy                                last(string $sortedField = 'id')
 * @method static PhysicalQualities|Proxy                                random(array $attributes = [])
 * @method static PhysicalQualities|Proxy                                randomOrCreate(array $attributes = [])
 * @method static PhysicalQualitiesRepository|ProxyRepositoryDecorator   repository()
 * @method static PhysicalQualities[]|Proxy[]                            all()
 * @method static PhysicalQualities[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static PhysicalQualities[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static PhysicalQualities[]|Proxy[]                            findBy(array $attributes)
 * @method static PhysicalQualities[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static PhysicalQualities[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        PhysicalQualities&Proxy<PhysicalQualities> create(array|callable $attributes = [])
 * @phpstan-method static PhysicalQualities&Proxy<PhysicalQualities> createOne(array $attributes = [])
 * @phpstan-method static PhysicalQualities&Proxy<PhysicalQualities> find(object|array|mixed $criteria)
 * @phpstan-method static PhysicalQualities&Proxy<PhysicalQualities> findOrCreate(array $attributes)
 * @phpstan-method static PhysicalQualities&Proxy<PhysicalQualities> first(string $sortedField = 'id')
 * @phpstan-method static PhysicalQualities&Proxy<PhysicalQualities> last(string $sortedField = 'id')
 * @phpstan-method static PhysicalQualities&Proxy<PhysicalQualities> random(array $attributes = [])
 * @phpstan-method static PhysicalQualities&Proxy<PhysicalQualities> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<PhysicalQualities, EntityRepository> repository()
 * @phpstan-method static list<PhysicalQualities&Proxy<PhysicalQualities>> all()
 * @phpstan-method static list<PhysicalQualities&Proxy<PhysicalQualities>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<PhysicalQualities&Proxy<PhysicalQualities>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<PhysicalQualities&Proxy<PhysicalQualities>> findBy(array $attributes)
 * @phpstan-method static list<PhysicalQualities&Proxy<PhysicalQualities>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<PhysicalQualities&Proxy<PhysicalQualities>> randomSet(int $number, array $attributes = [])
 */
final class PhysicalQualitiesFactory extends PersistentProxyObjectFactory
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

    protected function initialize(): self
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
