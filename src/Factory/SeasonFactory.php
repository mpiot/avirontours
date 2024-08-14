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

use App\Entity\Season;
use App\Repository\SeasonRepository;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<Season>
 *
 * @method        Season|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static Season|Proxy                                createOne(array $attributes = [])
 * @method static Season|Proxy                                find(object|array|mixed $criteria)
 * @method static Season|Proxy                                findOrCreate(array $attributes)
 * @method static Season|Proxy                                first(string $sortedField = 'id')
 * @method static Season|Proxy                                last(string $sortedField = 'id')
 * @method static Season|Proxy                                random(array $attributes = [])
 * @method static Season|Proxy                                randomOrCreate(array $attributes = [])
 * @method static SeasonRepository|ProxyRepositoryDecorator   repository()
 * @method static Season[]|Proxy[]                            all()
 * @method static Season[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static Season[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static Season[]|Proxy[]                            findBy(array $attributes)
 * @method static Season[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static Season[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Season&Proxy<Season> create(array|callable $attributes = [])
 * @phpstan-method static Season&Proxy<Season> createOne(array $attributes = [])
 * @phpstan-method static Season&Proxy<Season> find(object|array|mixed $criteria)
 * @phpstan-method static Season&Proxy<Season> findOrCreate(array $attributes)
 * @phpstan-method static Season&Proxy<Season> first(string $sortedField = 'id')
 * @phpstan-method static Season&Proxy<Season> last(string $sortedField = 'id')
 * @phpstan-method static Season&Proxy<Season> random(array $attributes = [])
 * @phpstan-method static Season&Proxy<Season> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<Season, EntityRepository> repository()
 * @phpstan-method static list<Season&Proxy<Season>> all()
 * @phpstan-method static list<Season&Proxy<Season>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Season&Proxy<Season>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Season&Proxy<Season>> findBy(array $attributes)
 * @phpstan-method static list<Season&Proxy<Season>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Season&Proxy<Season>> randomSet(int $number, array $attributes = [])
 */
final class SeasonFactory extends PersistentProxyObjectFactory
{
    public function active(): self
    {
        return $this->with(['active' => true]);
    }

    public function inactive(): self
    {
        return $this->with(['active' => false]);
    }

    public function subscriptionEnabled(): self
    {
        return $this->with(['subscriptionEnabled' => true]);
    }

    public function subscriptionDisabled(): self
    {
        return $this->with(['subscriptionEnabled' => false]);
    }

    public function seasonCategoriesDisplayed(): self
    {
        return $this->with(['seasonCategories' => SeasonCategoryFactory::new()->displayed()->many(2, 5)]);
    }

    public function seasonCategoriesNotDisplayed(): self
    {
        return $this->with(['seasonCategories' => SeasonCategoryFactory::new()->notDisplayed()->many(2, 5)]);
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->year(),
            'active' => self::faker()->boolean(),
            'subscriptionEnabled' => self::faker()->boolean(),
            'seasonCategories' => SeasonCategoryFactory::new()->many(2, 5),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->beforeInstantiate(function(Season $season) {})
    }

    public static function class(): string
    {
        return Season::class;
    }
}
