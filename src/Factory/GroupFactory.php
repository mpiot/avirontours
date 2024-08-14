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

use App\Entity\Group;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<Group>
 *
 * @method        Group|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static Group|Proxy                                createOne(array $attributes = [])
 * @method static Group|Proxy                                find(object|array|mixed $criteria)
 * @method static Group|Proxy                                findOrCreate(array $attributes)
 * @method static Group|Proxy                                first(string $sortedField = 'id')
 * @method static Group|Proxy                                last(string $sortedField = 'id')
 * @method static Group|Proxy                                random(array $attributes = [])
 * @method static Group|Proxy                                randomOrCreate(array $attributes = [])
 * @method static GroupRepository|ProxyRepositoryDecorator   repository()
 * @method static Group[]|Proxy[]                            all()
 * @method static Group[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static Group[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static Group[]|Proxy[]                            findBy(array $attributes)
 * @method static Group[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static Group[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Group&Proxy<Group> create(array|callable $attributes = [])
 * @phpstan-method static Group&Proxy<Group> createOne(array $attributes = [])
 * @phpstan-method static Group&Proxy<Group> find(object|array|mixed $criteria)
 * @phpstan-method static Group&Proxy<Group> findOrCreate(array $attributes)
 * @phpstan-method static Group&Proxy<Group> first(string $sortedField = 'id')
 * @phpstan-method static Group&Proxy<Group> last(string $sortedField = 'id')
 * @phpstan-method static Group&Proxy<Group> random(array $attributes = [])
 * @phpstan-method static Group&Proxy<Group> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<Group, EntityRepository> repository()
 * @phpstan-method static list<Group&Proxy<Group>> all()
 * @phpstan-method static list<Group&Proxy<Group>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Group&Proxy<Group>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Group&Proxy<Group>> findBy(array $attributes)
 * @phpstan-method static list<Group&Proxy<Group>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Group&Proxy<Group>> randomSet(int $number, array $attributes = [])
 */
final class GroupFactory extends PersistentProxyObjectFactory
{
    public function withMembers(): self
    {
        return $this->with([
            'members' => UserFactory::new()->many(1, 20),
        ]);
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this;
        // ->afterInstantiate(function(Group $group) {})
    }

    public static function class(): string
    {
        return Group::class;
    }
}
