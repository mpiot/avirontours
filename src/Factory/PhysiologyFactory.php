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

use App\Entity\Physiology;
use App\Repository\PhysiologyRepository;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<Physiology>
 *
 * @method        Physiology|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static Physiology|Proxy                                createOne(array $attributes = [])
 * @method static Physiology|Proxy                                find(object|array|mixed $criteria)
 * @method static Physiology|Proxy                                findOrCreate(array $attributes)
 * @method static Physiology|Proxy                                first(string $sortedField = 'id')
 * @method static Physiology|Proxy                                last(string $sortedField = 'id')
 * @method static Physiology|Proxy                                random(array $attributes = [])
 * @method static Physiology|Proxy                                randomOrCreate(array $attributes = [])
 * @method static PhysiologyRepository|ProxyRepositoryDecorator   repository()
 * @method static Physiology[]|Proxy[]                            all()
 * @method static Physiology[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static Physiology[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static Physiology[]|Proxy[]                            findBy(array $attributes)
 * @method static Physiology[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static Physiology[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Physiology&Proxy<Physiology> create(array|callable $attributes = [])
 * @phpstan-method static Physiology&Proxy<Physiology> createOne(array $attributes = [])
 * @phpstan-method static Physiology&Proxy<Physiology> find(object|array|mixed $criteria)
 * @phpstan-method static Physiology&Proxy<Physiology> findOrCreate(array $attributes)
 * @phpstan-method static Physiology&Proxy<Physiology> first(string $sortedField = 'id')
 * @phpstan-method static Physiology&Proxy<Physiology> last(string $sortedField = 'id')
 * @phpstan-method static Physiology&Proxy<Physiology> random(array $attributes = [])
 * @phpstan-method static Physiology&Proxy<Physiology> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<Physiology, EntityRepository> repository()
 * @phpstan-method static list<Physiology&Proxy<Physiology>> all()
 * @phpstan-method static list<Physiology&Proxy<Physiology>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Physiology&Proxy<Physiology>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Physiology&Proxy<Physiology>> findBy(array $attributes)
 * @phpstan-method static list<Physiology&Proxy<Physiology>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Physiology&Proxy<Physiology>> randomSet(int $number, array $attributes = [])
 */
final class PhysiologyFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        $max = self::faker()->numberBetween(180, 2150);

        return [
            'user' => UserFactory::new(),
            'lightAerobicHeartRateMin' => round($max * 0.65),
            'heavyAerobicHeartRateMin' => round($max * 0.70),
            'anaerobicThresholdHeartRateMin' => round($max * 0.80),
            'oxygenTransportationHeartRateMin' => round($max * 0.85),
            'anaerobicHeartRateMin' => round($max * 0.95),
            'maximumHeartRate' => $max,
            'maximumOxygenConsumption' => self::faker()->randomFloat(2, 20, 90),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->afterInstantiate(function(Physiology $physiology) {})
    }

    public static function class(): string
    {
        return Physiology::class;
    }
}
