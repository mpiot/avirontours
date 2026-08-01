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

use App\Entity\ShellDamage;
use App\Entity\ShellDamageCategory;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ShellDamage>
 */
final class ShellDamageFactory extends PersistentObjectFactory
{
    public function highlyDamaged(): self
    {
        return $this->with([
            'category' => ShellDamageCategoryFactory::new(['priority' => ShellDamageCategory::PRIORITY_HIGH]),
        ]);
    }

    public function mediumDamaged(): self
    {
        return $this->with([
            'category' => ShellDamageCategoryFactory::new(['priority' => ShellDamageCategory::PRIORITY_MEDIUM]),
        ]);
    }

    public function repaired(): self
    {
        return $this->with([
            'repairEndAt' => self::faker()->dateTimeThisYear(),
        ]);
    }

    public function notRepaired(): self
    {
        return $this->with([
            'repairEndAt' => null,
        ]);
    }

    protected function defaults(): array|callable
    {
        return [
            'category' => ShellDamageCategoryFactory::new(),
            'shell' => ShellFactory::new(),
            'description' => self::faker()->text(),
            'note' => self::faker()->text(),
            'repairStartAt' => self::faker()->optional()->dateTimeThisYear(),
            'repairEndAt' => self::faker()->optional()->dateTimeThisYear(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->beforeInstantiate(function(ShellDamage $shellDamage) {})
    }

    public static function class(): string
    {
        return ShellDamage::class;
    }
}
