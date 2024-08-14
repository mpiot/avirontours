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

use App\Entity\LicensePayment;
use App\Enum\PaymentMethod;
use App\Repository\LicensePaymentRepository;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<LicensePayment>
 *
 * @method        LicensePayment|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static LicensePayment|Proxy                                createOne(array $attributes = [])
 * @method static LicensePayment|Proxy                                find(object|array|mixed $criteria)
 * @method static LicensePayment|Proxy                                findOrCreate(array $attributes)
 * @method static LicensePayment|Proxy                                first(string $sortedField = 'id')
 * @method static LicensePayment|Proxy                                last(string $sortedField = 'id')
 * @method static LicensePayment|Proxy                                random(array $attributes = [])
 * @method static LicensePayment|Proxy                                randomOrCreate(array $attributes = [])
 * @method static LicensePaymentRepository|ProxyRepositoryDecorator   repository()
 * @method static LicensePayment[]|Proxy[]                            all()
 * @method static LicensePayment[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static LicensePayment[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static LicensePayment[]|Proxy[]                            findBy(array $attributes)
 * @method static LicensePayment[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static LicensePayment[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        LicensePayment&Proxy<LicensePayment> create(array|callable $attributes = [])
 * @phpstan-method static LicensePayment&Proxy<LicensePayment> createOne(array $attributes = [])
 * @phpstan-method static LicensePayment&Proxy<LicensePayment> find(object|array|mixed $criteria)
 * @phpstan-method static LicensePayment&Proxy<LicensePayment> findOrCreate(array $attributes)
 * @phpstan-method static LicensePayment&Proxy<LicensePayment> first(string $sortedField = 'id')
 * @phpstan-method static LicensePayment&Proxy<LicensePayment> last(string $sortedField = 'id')
 * @phpstan-method static LicensePayment&Proxy<LicensePayment> random(array $attributes = [])
 * @phpstan-method static LicensePayment&Proxy<LicensePayment> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<LicensePayment, EntityRepository> repository()
 * @phpstan-method static list<LicensePayment&Proxy<LicensePayment>> all()
 * @phpstan-method static list<LicensePayment&Proxy<LicensePayment>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<LicensePayment&Proxy<LicensePayment>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<LicensePayment&Proxy<LicensePayment>> findBy(array $attributes)
 * @phpstan-method static list<LicensePayment&Proxy<LicensePayment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<LicensePayment&Proxy<LicensePayment>> randomSet(int $number, array $attributes = [])
 */
final class LicensePaymentFactory extends PersistentProxyObjectFactory
{
    protected function defaults(): array|callable
    {
        return [
            'method' => self::faker()->randomElement(PaymentMethod::cases()),
            'amount' => self::faker()->numberBetween(2500, 30000),
        ];
    }

    protected function initialize(): self
    {
        return $this
            // ->afterInstantiate(function(LicensePayment $licensePayment): void {})
        ;
    }

    public static function class(): string
    {
        return LicensePayment::class;
    }
}
