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
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static SeasonCategory|Proxy                     createOne(array $attributes = [])
 * @method static SeasonCategory[]|Proxy[]                 createMany(int $number, $attributes = [])
 * @method static SeasonCategory|Proxy                     findOrCreate(array $attributes)
 * @method static SeasonCategory|Proxy                     random(array $attributes = [])
 * @method static SeasonCategory|Proxy                     randomOrCreate(array $attributes = [])
 * @method static SeasonCategory[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static SeasonCategory[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static SeasonCategoryRepository|RepositoryProxy repository()
 * @method        SeasonCategory|Proxy                     create($attributes = [])
 */
final class SeasonCategoryFactory extends ModelFactory
{
    public function displayed(): self
    {
        return $this->addState(['displayed' => true]);
    }

    public function notDisplayed(): self
    {
        return $this->addState(['displayed' => false]);
    }

    protected function getDefaults(): array
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

    protected static function getClass(): string
    {
        return SeasonCategory::class;
    }
}
