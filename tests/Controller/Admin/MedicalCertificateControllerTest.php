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

namespace App\Tests\Controller\Admin;

use App\Factory\MedicalCertificateFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class MedicalCertificateControllerTest extends AppWebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForAnonymousUser($method, $url): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForRegularUser($method, $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $medicalCertificate = MedicalCertificateFactory::createOne();
            $url = str_replace('{id}', (string) $medicalCertificate->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider()
    {
        yield ['GET', '/admin/medical-certificate/{id}/download'];
    }

    public function testDownloadMedicalCertificate(): void
    {
        $medicalCertificate = MedicalCertificateFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $client->request('GET', '/admin/medical-certificate/'.$medicalCertificate->getId().'/download');

        $this->assertResponseIsSuccessful();
    }
}
