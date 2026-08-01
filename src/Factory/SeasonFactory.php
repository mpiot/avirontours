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
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Season>
 */
final class SeasonFactory extends PersistentObjectFactory
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
        return $this->with(['seasonCategories' => SeasonCategoryFactory::new()->displayed()->many(2)]);
    }

    public function seasonCategoriesNotDisplayed(): self
    {
        return $this->with(['seasonCategories' => SeasonCategoryFactory::new()->notDisplayed()->many(2)]);
    }

    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->year(),
            'active' => self::faker()->boolean(),
            'subscriptionEnabled' => self::faker()->boolean(),
            'seasonCategories' => SeasonCategoryFactory::new()->many(2),
        ];
    }

    #[\Override]
    protected function initialize(): static
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
