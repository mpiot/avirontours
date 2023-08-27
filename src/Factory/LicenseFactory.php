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
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static License|Proxy                     createOne(array $attributes = [])
 * @method static License[]|Proxy[]                 createMany(int $number, $attributes = [])
 * @method static License|Proxy                     findOrCreate(array $attributes)
 * @method static License|Proxy                     random(array $attributes = [])
 * @method static License|Proxy                     randomOrCreate(array $attributes = [])
 * @method static License[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static License[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static LicenseRepository|RepositoryProxy repository()
 * @method        License|Proxy                     create($attributes = [])
 */
final class LicenseFactory extends ModelFactory
{
    public function annualActive(): self
    {
        return $this->addState([
            'seasonCategory' => SeasonCategoryFactory::new([
                'licenseType' => SeasonCategory::LICENSE_TYPE_ANNUAL,
                'season' => SeasonFactory::new()->active(),
            ]),
        ]);
    }

    public function indoorActive(): self
    {
        return $this->addState([
            'seasonCategory' => SeasonCategoryFactory::new([
                'licenseType' => SeasonCategory::LICENSE_TYPE_INDOOR,
                'season' => SeasonFactory::new()->active(),
            ]),
        ]);
    }

    public function annualInactive(): self
    {
        return $this->addState([
            'seasonCategory' => SeasonCategoryFactory::new([
                'licenseType' => SeasonCategory::LICENSE_TYPE_ANNUAL,
                'season' => SeasonFactory::new()->inactive(),
            ]),
        ]);
    }

    public function indoorInactive(): self
    {
        return $this->addState([
            'seasonCategory' => SeasonCategoryFactory::new([
                'licenseType' => SeasonCategory::LICENSE_TYPE_INDOOR,
                'season' => SeasonFactory::new()->inactive(),
            ]),
        ]);
    }

    public function withValidLicense(): self
    {
        return $this->addState([
            'marking' => ['validated' => 1],
        ]);
    }

    public function withInvalidLicense(): self
    {
        return $this->addState([
            'marking' => [],
        ]);
    }

    public function withPayments(): self
    {
        return $this->addState([
            'payments' => LicensePaymentFactory::new()->many(1, 5),
            'payedAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeThisYear()),
        ]);
    }

    protected function getDefaults(): array
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

    protected static function getClass(): string
    {
        return License::class;
    }
}
