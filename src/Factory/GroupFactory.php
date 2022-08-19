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
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<Group>
 *
 * @method static Group|Proxy                     createOne(array $attributes = [])
 * @method static Group[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static Group|Proxy                     find(object|array|mixed $criteria)
 * @method static Group|Proxy                     findOrCreate(array $attributes)
 * @method static Group|Proxy                     first(string $sortedField = 'id')
 * @method static Group|Proxy                     last(string $sortedField = 'id')
 * @method static Group|Proxy                     random(array $attributes = [])
 * @method static Group|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Group[]|Proxy[]                 all()
 * @method static Group[]|Proxy[]                 findBy(array $attributes)
 * @method static Group[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Group[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static GroupRepository|RepositoryProxy repository()
 * @method        Group|Proxy                     create(array|callable $attributes = [])
 */
final class GroupFactory extends ModelFactory
{
    public function withMembers(): self
    {
        return $this->addState([
            'members' => UserFactory::new()->many(1, 20),
        ]);
    }

    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->text(),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Group $group) {})
        ;
    }

    protected static function getClass(): string
    {
        return Group::class;
    }
}
