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

namespace App\Tests\Repository;

use App\Factory\LicenseFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LicenseRepositoryTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testHasValidLicenseForActiveSeasonWithValidatedMarking(): void
    {
        $user = UserFactory::createOne();
        LicenseFactory::new()->annualActive()->create([
            'user' => $user,
            'marking' => ['validated' => 1],
        ]);

        self::assertTrue(LicenseFactory::repository()->hasValidLicenseForActiveSeason($user));
    }

    public function testHasValidLicenseForActiveSeasonWithMedicalAndPaymentValidatedMarking(): void
    {
        $user = UserFactory::createOne();
        LicenseFactory::new()->annualActive()->create([
            'user' => $user,
            'marking' => ['medical_certificate_validated' => 1, 'payment_validated' => 1],
        ]);

        self::assertTrue(LicenseFactory::repository()->hasValidLicenseForActiveSeason($user));
    }

    public function testHasValidLicenseForActiveSeasonWithMedicalValidatedButPaymentPendingMarking(): void
    {
        $user = UserFactory::createOne();
        LicenseFactory::new()->annualActive()->create([
            'user' => $user,
            'marking' => ['medical_certificate_validated' => 1, 'wait_payment_validation' => 1],
        ]);

        self::assertFalse(LicenseFactory::repository()->hasValidLicenseForActiveSeason($user));
    }

    public function testHasValidLicenseForActiveSeasonWithWaitingValidationMarking(): void
    {
        $user = UserFactory::createOne();
        LicenseFactory::new()->annualActive()->create([
            'user' => $user,
            'marking' => ['wait_medical_certificate_validation' => 1, 'wait_payment_validation' => 1],
        ]);

        self::assertFalse(LicenseFactory::repository()->hasValidLicenseForActiveSeason($user));
    }

    public function testHasValidLicenseForActiveSeasonWithValidatedMarkingOnInactiveSeason(): void
    {
        $user = UserFactory::createOne();
        LicenseFactory::new()->annualInactive()->create([
            'user' => $user,
            'marking' => ['validated' => 1],
        ]);

        self::assertFalse(LicenseFactory::repository()->hasValidLicenseForActiveSeason($user));
    }

    public function testHasValidLicenseForActiveSeasonWithoutLicense(): void
    {
        $user = UserFactory::createOne();

        self::assertFalse(LicenseFactory::repository()->hasValidLicenseForActiveSeason($user));
    }
}
