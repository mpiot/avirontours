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

namespace App\Tests\Functional;

use App\Entity\MedicalCertificate;
use App\Entity\User;
use App\Tests\AppWebTestCase;

class ProfileControllerTest extends AppWebTestCase
{
    public function testRenew()
    {
        $client = static::createClient();
        $url = '/profile/renew';

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(404);

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Se réinscrire', [
            'renew[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'renew[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'renew[medicalCertificate][date]' => '2020-01-01',
        ]);
        $this->assertResponseRedirects();
        /** @var User $user */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['username' => 'a.user']);
        $this->assertCount(2, $user->getSeasonUsers());
        $this->assertSame(MedicalCertificate::TYPE_CERTIFICATE, $user->getSeasonUsers()->get(1)->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificate::LEVEL_COMPETITION, $user->getSeasonUsers()->get(1)->getMedicalCertificate()->getLevel());
        $this->assertSame('2020-01-01', $user->getSeasonUsers()->get(1)->getMedicalCertificate()->getdate()->format('Y-m-d'));

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(404);
    }
}
