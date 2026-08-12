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

namespace App\Tests\Controller;

use App\Entity\LegalGuardian;
use App\Enum\LegalGuardianRole;
use App\Factory\LicenseFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;

class PaymentAttestationControllerTest extends AppWebTestCase
{
    public function testDownloadPaymentAttestation(): void
    {
        $user = UserFactory::createOne();
        $license = LicenseFactory::new()->withPayments()->create([
            'user' => $user,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/payment-attestation/download/'.$license->getId());

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/pdf');
    }

    public function testDownloadPaymentAttestationOfAnUnpaidLicense(): void
    {
        $user = UserFactory::createOne();
        $license = LicenseFactory::createOne([
            'user' => $user,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/payment-attestation/download/'.$license->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCheckPaymentAttestation(): void
    {
        $license = LicenseFactory::new()->withPayments()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $client->request('GET', '/payment-attestation/check/'.$license->getUuid());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Attestation de paiement');
        $this->assertSelectorTextContains('.card-header', 'Attestation authentique');
        $this->assertCount(4, $crawler->filterXPath('//ul/li'));
        $this->assertStringContainsString($license->getUser()->getFullName(), $crawler->filterXPath('//ul/li[1]')->text());
        $this->assertStringContainsString($license->getSeasonCategory()->getSeason()->getExtendedName(), $crawler->filterXPath('//ul/li[2]')->text());
        $this->assertStringContainsString($license->getPayedAt()->format('Y'), $crawler->filterXPath('//ul/li[3]')->text());
        $this->assertStringContainsString('Montant réglé', $crawler->filterXPath('//ul/li[4]')->text());
    }

    public function testCheckPaymentAttestationOfAMinor(): void
    {
        $license = LicenseFactory::new()->withPayments()->create([
            'user' => UserFactory::new()->minor()->create([
                'firstLegalGuardian' => self::createLegalGuardian(LegalGuardianRole::Mother, 'Marie', 'Martin'),
                'secondLegalGuardian' => self::createLegalGuardian(LegalGuardianRole::Father, 'Paul', 'Martin'),
            ]),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $client->request('GET', '/payment-attestation/check/'.$license->getUuid());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Attestation de paiement');
        $this->assertSelectorTextContains('.card-header', 'Attestation authentique');
        $this->assertCount(5, $crawler->filterXPath('//ul/li'));
        $this->assertStringContainsString($license->getUser()->getFullName(), $crawler->filterXPath('//ul/li[1]')->text());
        $this->assertStringContainsString('Représentants légaux', $crawler->filterXPath('//ul/li[2]')->text());
        $this->assertStringContainsString('Marie Martin et Paul Martin', $crawler->filterXPath('//ul/li[2]')->text());
        $this->assertStringContainsString($license->getSeasonCategory()->getSeason()->getExtendedName(), $crawler->filterXPath('//ul/li[3]')->text());
        $this->assertStringContainsString($license->getPayedAt()->format('Y'), $crawler->filterXPath('//ul/li[4]')->text());
        $this->assertStringContainsString('Montant réglé', $crawler->filterXPath('//ul/li[5]')->text());
    }

    public function testCheckInvalidPaymentAttestation(): void
    {
        $uuid = Uuid::v4();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/payment-attestation/check/'.$uuid->toRfc4122());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Attestation de paiement');
        $this->assertSelectorTextContains('.card-header', 'Attestation introuvable');
    }

    public function testCheckPaymentAttestationOfAnUnpaidLicense(): void
    {
        $license = LicenseFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/payment-attestation/check/'.$license->getUuid());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('.card-header', 'Attestation introuvable');
    }

    private static function createLegalGuardian(LegalGuardianRole $role, string $firstName, string $lastName): LegalGuardian
    {
        return (new LegalGuardian())
            ->setRole($role)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setEmail(mb_strtolower("{$firstName}.{$lastName}@avirontours.fr"))
            ->setPhoneNumber('0600000000')
        ;
    }
}
