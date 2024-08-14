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
use App\Repository\ShellDamageRepository;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<ShellDamage>
 *
 * @method        ShellDamage|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static ShellDamage|Proxy                                createOne(array $attributes = [])
 * @method static ShellDamage|Proxy                                find(object|array|mixed $criteria)
 * @method static ShellDamage|Proxy                                findOrCreate(array $attributes)
 * @method static ShellDamage|Proxy                                first(string $sortedField = 'id')
 * @method static ShellDamage|Proxy                                last(string $sortedField = 'id')
 * @method static ShellDamage|Proxy                                random(array $attributes = [])
 * @method static ShellDamage|Proxy                                randomOrCreate(array $attributes = [])
 * @method static ShellDamageRepository|ProxyRepositoryDecorator   repository()
 * @method static ShellDamage[]|Proxy[]                            all()
 * @method static ShellDamage[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static ShellDamage[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static ShellDamage[]|Proxy[]                            findBy(array $attributes)
 * @method static ShellDamage[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static ShellDamage[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        ShellDamage&Proxy<ShellDamage> create(array|callable $attributes = [])
 * @phpstan-method static ShellDamage&Proxy<ShellDamage> createOne(array $attributes = [])
 * @phpstan-method static ShellDamage&Proxy<ShellDamage> find(object|array|mixed $criteria)
 * @phpstan-method static ShellDamage&Proxy<ShellDamage> findOrCreate(array $attributes)
 * @phpstan-method static ShellDamage&Proxy<ShellDamage> first(string $sortedField = 'id')
 * @phpstan-method static ShellDamage&Proxy<ShellDamage> last(string $sortedField = 'id')
 * @phpstan-method static ShellDamage&Proxy<ShellDamage> random(array $attributes = [])
 * @phpstan-method static ShellDamage&Proxy<ShellDamage> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<ShellDamage, EntityRepository> repository()
 * @phpstan-method static list<ShellDamage&Proxy<ShellDamage>> all()
 * @phpstan-method static list<ShellDamage&Proxy<ShellDamage>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<ShellDamage&Proxy<ShellDamage>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<ShellDamage&Proxy<ShellDamage>> findBy(array $attributes)
 * @phpstan-method static list<ShellDamage&Proxy<ShellDamage>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<ShellDamage&Proxy<ShellDamage>> randomSet(int $number, array $attributes = [])
 */
final class ShellDamageFactory extends PersistentProxyObjectFactory
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

    protected function initialize(): self
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
