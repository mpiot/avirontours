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
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @extends ModelFactory<LicensePayment>
 *
 * @method        LicensePayment|Proxy                     create(array|callable $attributes = [])
 * @method static LicensePayment|Proxy                     createOne(array $attributes = [])
 * @method static LicensePayment|Proxy                     find(object|array|mixed $criteria)
 * @method static LicensePayment|Proxy                     findOrCreate(array $attributes)
 * @method static LicensePayment|Proxy                     first(string $sortedField = 'id')
 * @method static LicensePayment|Proxy                     last(string $sortedField = 'id')
 * @method static LicensePayment|Proxy                     random(array $attributes = [])
 * @method static LicensePayment|Proxy                     randomOrCreate(array $attributes = [])
 * @method static LicensePaymentRepository|RepositoryProxy repository()
 * @method static LicensePayment[]|Proxy[]                 all()
 * @method static LicensePayment[]|Proxy[]                 createMany(int $number, array|callable $attributes = [])
 * @method static LicensePayment[]|Proxy[]                 createSequence(iterable|callable $sequence)
 * @method static LicensePayment[]|Proxy[]                 findBy(array $attributes)
 * @method static LicensePayment[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static LicensePayment[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        Proxy<LicensePayment> create(array|callable $attributes = [])
 * @phpstan-method static Proxy<LicensePayment> createOne(array $attributes = [])
 * @phpstan-method static Proxy<LicensePayment> find(object|array|mixed $criteria)
 * @phpstan-method static Proxy<LicensePayment> findOrCreate(array $attributes)
 * @phpstan-method static Proxy<LicensePayment> first(string $sortedField = 'id')
 * @phpstan-method static Proxy<LicensePayment> last(string $sortedField = 'id')
 * @phpstan-method static Proxy<LicensePayment> random(array $attributes = [])
 * @phpstan-method static Proxy<LicensePayment> randomOrCreate(array $attributes = [])
 * @phpstan-method static RepositoryProxy<LicensePayment> repository()
 * @phpstan-method static list<Proxy<LicensePayment>> all()
 * @phpstan-method static list<Proxy<LicensePayment>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<Proxy<LicensePayment>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<Proxy<LicensePayment>> findBy(array $attributes)
 * @phpstan-method static list<Proxy<LicensePayment>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<Proxy<LicensePayment>> randomSet(int $number, array $attributes = [])
 */
final class LicensePaymentFactory extends ModelFactory
{
    protected function getDefaults(): array
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

    protected static function getClass(): string
    {
        return LicensePayment::class;
    }
}
