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

use App\Entity\PostalCode;
use App\Repository\PostalCodeRepository;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<PostalCode>
 *
 * @method        PostalCode|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static PostalCode|Proxy                                createOne(array $attributes = [])
 * @method static PostalCode|Proxy                                find(object|array|mixed $criteria)
 * @method static PostalCode|Proxy                                findOrCreate(array $attributes)
 * @method static PostalCode|Proxy                                first(string $sortedField = 'id')
 * @method static PostalCode|Proxy                                last(string $sortedField = 'id')
 * @method static PostalCode|Proxy                                random(array $attributes = [])
 * @method static PostalCode|Proxy                                randomOrCreate(array $attributes = [])
 * @method static PostalCodeRepository|ProxyRepositoryDecorator   repository()
 * @method static PostalCode[]|Proxy[]                            all()
 * @method static PostalCode[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static PostalCode[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static PostalCode[]|Proxy[]                            findBy(array $attributes)
 * @method static PostalCode[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static PostalCode[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        PostalCode&Proxy<PostalCode> create(array|callable $attributes = [])
 * @phpstan-method static PostalCode&Proxy<PostalCode> createOne(array $attributes = [])
 * @phpstan-method static PostalCode&Proxy<PostalCode> find(object|array|mixed $criteria)
 * @phpstan-method static PostalCode&Proxy<PostalCode> findOrCreate(array $attributes)
 * @phpstan-method static PostalCode&Proxy<PostalCode> first(string $sortedField = 'id')
 * @phpstan-method static PostalCode&Proxy<PostalCode> last(string $sortedField = 'id')
 * @phpstan-method static PostalCode&Proxy<PostalCode> random(array $attributes = [])
 * @phpstan-method static PostalCode&Proxy<PostalCode> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<PostalCode, EntityRepository> repository()
 * @phpstan-method static list<PostalCode&Proxy<PostalCode>> all()
 * @phpstan-method static list<PostalCode&Proxy<PostalCode>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<PostalCode&Proxy<PostalCode>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<PostalCode&Proxy<PostalCode>> findBy(array $attributes)
 * @phpstan-method static list<PostalCode&Proxy<PostalCode>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<PostalCode&Proxy<PostalCode>> randomSet(int $number, array $attributes = [])
 */
final class PostalCodeFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'postalCode' => self::faker()->postcode(),
            'city' => self::faker()->city(),
        ];
    }

    protected function initialize(): self
    {
        return $this;
        // ->afterInstantiate(function(PostalCode $postalCode): void {})
    }

    public static function class(): string
    {
        return PostalCode::class;
    }
}
