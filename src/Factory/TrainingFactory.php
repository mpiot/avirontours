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

use App\Entity\Training;
use App\Enum\SportType;
use App\Enum\TrainingType;
use App\Repository\TrainingRepository;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<Training>
 *
 * @method        Training|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static Training|Proxy                                createOne(array $attributes = [])
 * @method static Training|Proxy                                find(object|array|mixed $criteria)
 * @method static Training|Proxy                                findOrCreate(array $attributes)
 * @method static Training|Proxy                                first(string $sortedField = 'id')
 * @method static Training|Proxy                                last(string $sortedField = 'id')
 * @method static Training|Proxy                                random(array $attributes = [])
 * @method static Training|Proxy                                randomOrCreate(array $attributes = [])
 * @method static TrainingRepository|ProxyRepositoryDecorator   repository()
 * @method static Training[]|Proxy[]                            all()
 * @method static Training[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static Training[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static Training[]|Proxy[]                            findBy(array $attributes)
 * @method static Training[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static Training[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Training&Proxy<Training> create(array|callable $attributes = [])
 * @phpstan-method static Training&Proxy<Training> createOne(array $attributes = [])
 * @phpstan-method static Training&Proxy<Training> find(object|array|mixed $criteria)
 * @phpstan-method static Training&Proxy<Training> findOrCreate(array $attributes)
 * @phpstan-method static Training&Proxy<Training> first(string $sortedField = 'id')
 * @phpstan-method static Training&Proxy<Training> last(string $sortedField = 'id')
 * @phpstan-method static Training&Proxy<Training> random(array $attributes = [])
 * @phpstan-method static Training&Proxy<Training> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<Training, EntityRepository> repository()
 * @phpstan-method static list<Training&Proxy<Training>> all()
 * @phpstan-method static list<Training&Proxy<Training>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Training&Proxy<Training>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Training&Proxy<Training>> findBy(array $attributes)
 * @phpstan-method static list<Training&Proxy<Training>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Training&Proxy<Training>> randomSet(int $number, array $attributes = [])
 */
final class TrainingFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'user' => UserFactory::new(),
            'trainedAt' => self::faker()->dateTimeThisYear(),
            'duration' => self::faker()->numberBetween(12000, 72000),
            'distance' => self::faker()->numberBetween(8000, 20000),
            'sport' => self::faker()->randomElement(SportType::cases()),
            'type' => self::faker()->randomElement(TrainingType::cases()),
            'feeling' => self::faker()->randomFloat(1, 0, 1),
            'comment' => self::faker()->optional()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->afterInstantiate(function(Training $training) {})
    }

    public static function class(): string
    {
        return Training::class;
    }
}
