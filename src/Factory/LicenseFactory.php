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

use App\Entity\License;
use App\Entity\SeasonCategory;
use App\Repository\LicenseRepository;
use Doctrine\ORM\EntityRepository;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Zenstruck\Foundry\Persistence\ProxyRepositoryDecorator;
use Zenstruck\Foundry\Proxy;

/**
 * @extends PersistentProxyObjectFactory<License>
 *
 * @method        License|\Zenstruck\Foundry\Persistence\Proxy create(array|callable $attributes = [])
 * @method static License|Proxy                                createOne(array $attributes = [])
 * @method static License|Proxy                                find(object|array|mixed $criteria)
 * @method static License|Proxy                                findOrCreate(array $attributes)
 * @method static License|Proxy                                first(string $sortedField = 'id')
 * @method static License|Proxy                                last(string $sortedField = 'id')
 * @method static License|Proxy                                random(array $attributes = [])
 * @method static License|Proxy                                randomOrCreate(array $attributes = [])
 * @method static LicenseRepository|ProxyRepositoryDecorator   repository()
 * @method static License[]|Proxy[]                            all()
 * @method static License[]|Proxy[]                            createMany(int $number, array|callable $attributes = [])
 * @method static License[]|Proxy[]                            createSequence(iterable|callable $sequence)
 * @method static License[]|Proxy[]                            findBy(array $attributes)
 * @method static License[]|Proxy[]                            randomRange(int $min, int $max, array $attributes = [])
 * @method static License[]|Proxy[]                            randomSet(int $number, array $attributes = [])
 *
 * @phpstan-method        License&Proxy<License> create(array|callable $attributes = [])
 * @phpstan-method static License&Proxy<License> createOne(array $attributes = [])
 * @phpstan-method static License&Proxy<License> find(object|array|mixed $criteria)
 * @phpstan-method static License&Proxy<License> findOrCreate(array $attributes)
 * @phpstan-method static License&Proxy<License> first(string $sortedField = 'id')
 * @phpstan-method static License&Proxy<License> last(string $sortedField = 'id')
 * @phpstan-method static License&Proxy<License> random(array $attributes = [])
 * @phpstan-method static License&Proxy<License> randomOrCreate(array $attributes = [])
 * @phpstan-method static ProxyRepositoryDecorator<License, EntityRepository> repository()
 * @phpstan-method static list<License&Proxy<License>> all()
 * @phpstan-method static list<License&Proxy<License>> createMany(int $number, array|callable $attributes = [])
 * @phpstan-method static list<License&Proxy<License>> createSequence(iterable|callable $sequence)
 * @phpstan-method static list<License&Proxy<License>> findBy(array $attributes)
 * @phpstan-method static list<License&Proxy<License>> randomRange(int $min, int $max, array $attributes = [])
 * @phpstan-method static list<License&Proxy<License>> randomSet(int $number, array $attributes = [])
 */
final class LicenseFactory extends PersistentProxyObjectFactory
{
    public function annualActive(): self
    {
        return $this->with([
            'seasonCategory' => SeasonCategoryFactory::new([
                'licenseType' => SeasonCategory::LICENSE_TYPE_ANNUAL,
                'season' => SeasonFactory::new()->active(),
            ]),
        ]);
    }

    public function indoorActive(): self
    {
        return $this->with([
            'seasonCategory' => SeasonCategoryFactory::new([
                'licenseType' => SeasonCategory::LICENSE_TYPE_INDOOR,
                'season' => SeasonFactory::new()->active(),
            ]),
        ]);
    }

    public function annualInactive(): self
    {
        return $this->with([
            'seasonCategory' => SeasonCategoryFactory::new([
                'licenseType' => SeasonCategory::LICENSE_TYPE_ANNUAL,
                'season' => SeasonFactory::new()->inactive(),
            ]),
        ]);
    }

    public function indoorInactive(): self
    {
        return $this->with([
            'seasonCategory' => SeasonCategoryFactory::new([
                'licenseType' => SeasonCategory::LICENSE_TYPE_INDOOR,
                'season' => SeasonFactory::new()->inactive(),
            ]),
        ]);
    }

    public function withValidLicense(): self
    {
        return $this->with([
            'marking' => ['validated' => 1],
        ]);
    }

    public function withInvalidLicense(): self
    {
        return $this->with([
            'marking' => [],
        ]);
    }

    public function withPayments(): self
    {
        return $this->with([
            'payments' => LicensePaymentFactory::new()->many(1, 5),
            'payedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeThisYear()),
        ]);
    }

    protected function defaults(): array|callable
    {
        return [
            'seasonCategory' => SeasonCategoryFactory::new(['season' => SeasonFactory::new()]),
            'user' => UserFactory::new(),
            'marking' => self::faker()->randomElement([
                [],
                ['wait_medical_certificate_validation' => 1, 'wait_payment_validation' => 1],
                ['wait_medical_certificate_validation' => 1, 'payment_validated' => 1],
                ['medical_certificate_rejected' => 1, 'wait_payment_validation' => 1],
                ['medical_certificate_rejected' => 1, 'payment_validated' => 1],
                ['medical_certificate_validated' => 1, 'wait_payment_validation' => 1],
                ['medical_certificate_validated' => 1, 'payment_validated' => 1],
                ['validated' => 1],
            ]),
            'medicalCertificate' => MedicalCertificateFactory::new(),
            'optionalInsurance' => self::faker()->boolean(),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->beforeInstantiate(function(License $license) {})
    }

    public static function class(): string
    {
        return License::class;
    }
}
