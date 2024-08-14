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

use App\Entity\WorkoutMaximumLoad;
use App\Repository\WorkoutMaximumLoadRepository;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<WorkoutMaximumLoad>
 *
 * @method        WorkoutMaximumLoad|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static WorkoutMaximumLoad|Proxy                                createOne(array $attributes = [])
 * @method static WorkoutMaximumLoad|Proxy                                find(object|array|mixed $criteria)
 * @method static WorkoutMaximumLoad|Proxy                                findOrCreate(array $attributes)
 * @method static WorkoutMaximumLoad|Proxy                                first(string $sortedField = 'id')
 * @method static WorkoutMaximumLoad|Proxy                                last(string $sortedField = 'id')
 * @method static WorkoutMaximumLoad|Proxy                                random(array $attributes = [])
 * @method static WorkoutMaximumLoad|Proxy                                randomOrCreate(array $attributes = [])
 * @method static WorkoutMaximumLoadRepository|ProxyRepositoryDecorator   repository()
 * @method static WorkoutMaximumLoad[]|Proxy[]                            all()
 * @method static WorkoutMaximumLoad[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static WorkoutMaximumLoad[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static WorkoutMaximumLoad[]|Proxy[]                            findBy(array $attributes)
 * @method static WorkoutMaximumLoad[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static WorkoutMaximumLoad[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad> create(array|callable $attributes = [])
 * @phpstan-method static WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad> createOne(array $attributes = [])
 * @phpstan-method static WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad> find(object|array|mixed $criteria)
 * @phpstan-method static WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad> findOrCreate(array $attributes)
 * @phpstan-method static WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad> first(string $sortedField = 'id')
 * @phpstan-method static WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad> last(string $sortedField = 'id')
 * @phpstan-method static WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad> random(array $attributes = [])
 * @phpstan-method static WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<WorkoutMaximumLoad, EntityRepository> repository()
 * @phpstan-method static list<WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad>> all()
 * @phpstan-method static list<WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad>> findBy(array $attributes)
 * @phpstan-method static list<WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<WorkoutMaximumLoad&Proxy<WorkoutMaximumLoad>> randomSet(int $number, array $attributes = [])
 */
final class WorkoutMaximumLoadFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new(),
            'rowingTirage' => self::faker()->numberBetween(20, 200),
            'benchPress' => self::faker()->numberBetween(20, 200),
            'squat' => self::faker()->numberBetween(20, 200),
            'legPress' => self::faker()->numberBetween(20, 200),
            'clean' => self::faker()->numberBetween(20, 200),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->afterInstantiate(function(WorkoutMaximumLoad $workoutMaximumLoad) {})
    }

    public static function class(): string
    {
        return WorkoutMaximumLoad::class;
    }
}
