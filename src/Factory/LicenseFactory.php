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

use App\Entity\License;
use App\Entity\SeasonCategory;
use App\Repository\LicenseRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static            License|Proxy findOrCreate(array $attributes)
 * @method static            License|Proxy random()
 * @method static            License[]|Proxy[] randomSet(int $number)
 * @method static            License[]|Proxy[] randomRange(int $min, int $max)
 * @method static            LicenseRepository|RepositoryProxy repository()
 * @method License|Proxy     create($attributes = [])
 * @method License[]|Proxy[] createMany(int $number, $attributes = [])
 */
final class LicenseFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'seasonCategory' => SeasonCategoryFactory::new()->create(['season' => SeasonFactory::new()->create()]),
            'user' => UserFactory::new()->create(),
            'medicalCertificate' => MedicalCertificateFactory::new()->create(),
            'marking' => self::faker()->randomElement([
                null,
                ['wait_medical_certificate_validation' => 1, 'wait_payment_validation' => 1],
                ['wait_medical_certificate_validation' => 1, 'payment_validated' => 1],
                ['medical_certificate_rejected' => 1, 'wait_payment_validation' => 1],
                ['medical_certificate_rejected' => 1, 'payment_validated' => 1],
                ['medical_certificate_validated' => 1, 'wait_payment_validation' => 1],
                ['medical_certificate_validated' => 1, 'payment_validated' => 1],
                ['validated' => 1],
            ]),
        ];
    }

    public function logbookUsable(): self
    {
        return $this->addState([
            'seasonCategory' => SeasonCategoryFactory::new()->create([
                'licenseType' => SeasonCategory::LICENSE_TYPE_ANNUAL,
                'season' => SeasonFactory::new()->active()->create(),
            ]),
            'marking' => ['validated' => 1],
        ]);
    }

    public function logbookUnusable(): self
    {
        return $this->addState([
            'seasonCategory' => SeasonCategoryFactory::new()->create([
                'licenseType' => SeasonCategory::LICENSE_TYPE_ANNUAL,
                'season' => SeasonFactory::new()->active()->create(),
            ]),
            'marking' => null,
        ]);
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this
            // ->beforeInstantiate(function(License $license) {})
        ;
    }

    protected static function getClass(): string
    {
        return License::class;
    }
}
