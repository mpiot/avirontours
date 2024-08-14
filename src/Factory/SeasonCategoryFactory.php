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

use App\Entity\SeasonCategory;
use App\Repository\SeasonCategoryRepository;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<SeasonCategory>
 *
 * @method        SeasonCategory|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static SeasonCategory|Proxy                                createOne(array $attributes = [])
 * @method static SeasonCategory|Proxy                                find(object|array|mixed $criteria)
 * @method static SeasonCategory|Proxy                                findOrCreate(array $attributes)
 * @method static SeasonCategory|Proxy                                first(string $sortedField = 'id')
 * @method static SeasonCategory|Proxy                                last(string $sortedField = 'id')
 * @method static SeasonCategory|Proxy                                random(array $attributes = [])
 * @method static SeasonCategory|Proxy                                randomOrCreate(array $attributes = [])
 * @method static SeasonCategoryRepository|ProxyRepositoryDecorator   repository()
 * @method static SeasonCategory[]|Proxy[]                            all()
 * @method static SeasonCategory[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static SeasonCategory[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static SeasonCategory[]|Proxy[]                            findBy(array $attributes)
 * @method static SeasonCategory[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static SeasonCategory[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        SeasonCategory&Proxy<SeasonCategory> create(array|callable $attributes = [])
 * @phpstan-method static SeasonCategory&Proxy<SeasonCategory> createOne(array $attributes = [])
 * @phpstan-method static SeasonCategory&Proxy<SeasonCategory> find(object|array|mixed $criteria)
 * @phpstan-method static SeasonCategory&Proxy<SeasonCategory> findOrCreate(array $attributes)
 * @phpstan-method static SeasonCategory&Proxy<SeasonCategory> first(string $sortedField = 'id')
 * @phpstan-method static SeasonCategory&Proxy<SeasonCategory> last(string $sortedField = 'id')
 * @phpstan-method static SeasonCategory&Proxy<SeasonCategory> random(array $attributes = [])
 * @phpstan-method static SeasonCategory&Proxy<SeasonCategory> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<SeasonCategory, EntityRepository> repository()
 * @phpstan-method static list<SeasonCategory&Proxy<SeasonCategory>> all()
 * @phpstan-method static list<SeasonCategory&Proxy<SeasonCategory>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<SeasonCategory&Proxy<SeasonCategory>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<SeasonCategory&Proxy<SeasonCategory>> findBy(array $attributes)
 * @phpstan-method static list<SeasonCategory&Proxy<SeasonCategory>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<SeasonCategory&Proxy<SeasonCategory>> randomSet(int $number, array $attributes = [])
 */
final class SeasonCategoryFactory extends PersistentProxyObjectFactory
{
    public function displayed(): self
    {
        return $this->with(['displayed' => true]);
    }

    public function notDisplayed(): self
    {
        return $this->with(['displayed' => false]);
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->sentence(),
            'price' => self::faker()->randomElement([120, 200, 320]),
            'licenseType' => self::faker()->randomElement(SeasonCategory::getAvailableLicenseTypes()),
            'description' => self::faker()->text(),
            'displayed' => self::faker()->boolean(0.8),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->beforeInstantiate(function(SeasonCategory $seasonCategory) {})
    }

    public static function class(): string
    {
        return SeasonCategory::class;
    }
}
