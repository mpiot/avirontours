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

namespace App\Tests\Service;

use App\Enum\PaymentMethod;
use App\Factory\LicenseFactory;
use App\Factory\LicensePaymentFactory;
use App\Factory\SeasonFactory;
use App\Factory\UserFactory;
use App\Service\SeasonCsvGenerator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class SeasonCsvGeneratorTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testExportPayments(): void
    {
        $season = SeasonFactory::createOne();
        $seasonCategory = $season->getSeasonCategories()->first();

        $userWithOnePayment = UserFactory::createOne(['firstName' => 'Solo', 'lastName' => 'Rower']);
        LicensePaymentFactory::createOne([
            'license' => LicenseFactory::createOne(['user' => $userWithOnePayment, 'seasonCategory' => $seasonCategory]),
            'method' => PaymentMethod::Cash,
            'amount' => 12345,
        ]);

        $userWithMultiplePayments = UserFactory::createOne(['firstName' => 'Multi', 'lastName' => 'Rower']);
        $userWithMultiplePaymentsLicense = LicenseFactory::createOne(['user' => $userWithMultiplePayments, 'seasonCategory' => $seasonCategory]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::BankTransfer, 'amount' => 1011]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::BankTransfer, 'amount' => 1012]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::Check, 'amount' => 2021]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::Check, 'amount' => 2022]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::VacationCheck, 'amount' => 3031]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::VacationCheck, 'amount' => 3032]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::Online, 'amount' => 4041]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::Online, 'amount' => 4042]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::Cash, 'amount' => 5051]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::Cash, 'amount' => 5052]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::PassSport, 'amount' => 6061]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::PassSport, 'amount' => 6062]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::Yelp, 'amount' => 7071]);
        LicensePaymentFactory::createOne(['license' => $userWithMultiplePaymentsLicense, 'method' => PaymentMethod::Yelp, 'amount' => 7072]);

        self::getContainer()->get('doctrine')->getManager()->clear();

        $csv = self::getContainer()->get(SeasonCsvGenerator::class)->exportPayments($season);

        self::assertSame(
            $csv,
            <<<'EOD'
Prénom;Nom;Chèque;"Chèque 2";"Chèques vacance";"Chèques vacance 2";"En ligne (HelloAsso)";"En ligne (HelloAsso) 2";Liquide;"Liquide 2";Pass'Sport;"Pass'Sport 2";Virement;"Virement 2";Yelp;"Yelp 2"
Multi;Rower;20.21;20.22;30.31;30.32;40.41;40.42;50.51;50.52;60.61;60.62;10.11;10.12;70.71;70.72
Solo;Rower;;;;;;;123.45;;;;;;;

EOD
        );
    }
}
