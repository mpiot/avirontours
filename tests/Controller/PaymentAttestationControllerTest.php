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

use App\Factory\LicenseFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\Uid\Uuid;

class PaymentAttestationControllerTest extends AppWebTestCase
{
    public function testDownloadPaymentAttestation(): void
    {
        $user = UserFactory::createOne();
        $license = LicenseFactory::createOne([
            'user' => $user,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user->object());
        $client->request('GET', "/payment-attestation/download/{$license->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/pdf');
    }

    public function testCheckPaymentAttestation(): void
    {
        $license = LicenseFactory::new()->withPayments()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $client->request('GET', "/payment-attestation/check/{$license->getUuid()}");

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Attestation de paiement Nom - Prénom: ', $crawler->filter('div.border-success')->text());
        $this->assertStringContainsString('Montant: ', $crawler->filter('div.border-success')->text());
        $this->assertStringContainsString('Saison: ', $crawler->filter('div.border-success')->text());
    }

    public function testCheckInvalidPaymentAttestation(): void
    {
        $uuid = Uuid::v4();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $client->request('GET', "/payment-attestation/check/{$uuid->toRfc4122()}");

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Attestation de paiement Nous n\'avons trouvé aucune licence.', $crawler->filter('div.border-danger')->text());
    }
}
