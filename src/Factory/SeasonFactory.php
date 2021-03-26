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
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static       Season|Proxy createOne(array $attributes = [])
 * @method static       Season[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static       Season|Proxy findOrCreate(array $attributes)
 * @method static       Season|Proxy random(array $attributes = [])
 * @method static       Season|Proxy randomOrCreate(array $attributes = [])
 * @method static       Season[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static       Season[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static       SeasonRepository|RepositoryProxy repository()
 * @method Season|Proxy create($attributes = [])
 */
final class SeasonFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->year,
            'active' => self::faker()->boolean,
            'subscriptionEnabled' => self::faker()->boolean,
            'seasonCategories' => SeasonCategoryFactory::new()->many(2, 5),
        ];
    }

    public function active(): self
    {
        return $this->addState(['active' => true]);
    }

    public function inactive(): self
    {
        return $this->addState(['active' => false]);
    }

    public function subscriptionEnabled(): self
    {
        return $this->addState(['subscriptionEnabled' => true]);
    }

    public function subscriptionDisabled(): self
    {
        return $this->addState(['subscriptionEnabled' => false]);
    }

    public function seasonCategoriesDisplayed(): self
    {
        return $this->addState(['seasonCategories' => SeasonCategoryFactory::new()->displayed()->many(2, 5)]);
    }

    public function seasonCategoriesNotDisplayed(): self
    {
        return $this->addState(['seasonCategories' => SeasonCategoryFactory::new()->notDisplayed()->many(2, 5)]);
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->beforeInstantiate(function(Season $season) {})
        ;
    }

    protected static function getClass(): string
    {
        return Season::class;
    }
}
