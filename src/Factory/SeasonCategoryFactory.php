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
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<SeasonCategory>
 */
final class SeasonCategoryFactory extends PersistentObjectFactory
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

    #[\Override]
    protected function initialize(): static
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
