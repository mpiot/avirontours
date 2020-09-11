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

namespace App\Tests\Controller\Admin;

use App\Entity\License;
use App\Entity\MedicalCertificate;
use App\Tests\AppWebTestCase;

class LicenseControllerTest extends AppWebTestCase
{
    public function testNewLicense()
    {
        $client = static::createClient();
        $url = '/admin/season/2/license/new';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'license[medicalCertificate][date]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('label[for="license_user"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('label[for="license_seasonCategory"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_medicalCertificate_level')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_medicalCertificate_type')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="license_medicalCertificate_date"] .form-error-message')->text());
        $this->assertCount(5, $crawler->filter('.form-error-message'));

        $form = $crawler->selectButton('Sauver')->form([
            'license[user]' => 2,
            'license[seasonCategory]' => 7,
            'license[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'license[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'license[medicalCertificate][date]' => '2020-05-01',
        ]);
        $form['license[medicalCertificate][file][file]']->upload(__DIR__.'/../../../src/DataFixtures/Files/medical-certificate.pdf');
        $client->submit($form);
        $this->assertResponseRedirects();
        $license = $this->getEntityManager()->getRepository(License::class)->findOneBy([], ['id' => 'DESC']);
        $this->assertInstanceOf(License::class, $license);
        $this->assertSame(2, $license->getUser()->getId());
        $this->assertSame(7, $license->getSeasonCategory()->getId());
        $this->assertSame(MedicalCertificate::TYPE_CERTIFICATE, $license->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificate::LEVEL_COMPETITION, $license->getMedicalCertificate()->getLevel());
        $this->assertSame('2020-05-01', $license->getMedicalCertificate()->getDate()->format('Y-m-d'));
    }

    public function testNewLicenseNonUnique()
    {
        $client = static::createClient();
        $url = '/admin/season/2/license/new';

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'license[user]' => 3,
            'license[seasonCategory]' => 7,
            'license[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'license[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'license[medicalCertificate][date]' => '2020-05-01',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Déjà inscrit pour cette saison.', $crawler->filter('.form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));
    }

    public function testEditLicense()
    {
        $client = static::createClient();
        $url = '/admin/season/2/license/6/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'license_edit[seasonCategory]' => 6,
            'license_edit[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'license_edit[medicalCertificate][level]' => MedicalCertificate::LEVEL_PRACTICE,
            'license_edit[medicalCertificate][date]' => '2020-05-01',
        ]);
        $this->assertResponseRedirects();
        $license = $this->getEntityManager()->getRepository(License::class)->find(6);
        $this->assertSame(6, $license->getSeasonCategory()->getId());
        $this->assertSame(MedicalCertificate::TYPE_CERTIFICATE, $license->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificate::LEVEL_PRACTICE, $license->getMedicalCertificate()->getLevel());
        $this->assertSame('2020-05-01', $license->getMedicalCertificate()->getDate()->format('Y-m-d'));
    }

    public function testDeleteLicense()
    {
        $client = static::createClient();
        $url = '/admin/season/2/license/6/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');
        $this->assertResponseRedirects('/admin/season/2');
        $license = $this->getEntityManager()->getRepository(License::class)->find(6);
        $this->assertNull($license);
    }

    public function testApplyMarking()
    {
        $client = static::createClient();
        $url = '/admin/season/2';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Rejeter le certificat médical');
        $this->assertResponseRedirects($url);
        $license = $this->getEntityManager()->getRepository(License::class)->find(9);
        $this->assertSame([
            'wait_payment_validation' => 1,
            'medical_certificate_rejected' => 1,
        ], $license->getMarking());

        $client->followRedirect();
        $client->submitForm('Passer le certificat en attente de validation');
        $this->assertResponseRedirects($url);
        $license = $this->getEntityManager()->getRepository(License::class)->find(9);
        $this->assertSame([
            'wait_payment_validation' => 1,
            'wait_medical_certificate_validation' => 1,
        ], $license->getMarking());

        $client->followRedirect();
        $client->submitForm('Valider le certificat médical');
        $this->assertResponseRedirects($url);
        $license = $this->getEntityManager()->getRepository(License::class)->find(9);
        $this->assertSame([
            'wait_payment_validation' => 1,
            'medical_certificate_validated' => 1,
        ], $license->getMarking());

        $client->followRedirect();
        $client->submitForm('Valider le paiement');
        $this->assertResponseRedirects($url);
        $license = $this->getEntityManager()->getRepository(License::class)->find(9);
        $this->assertSame([
            'medical_certificate_validated' => 1,
            'payment_validated' => 1,
        ], $license->getMarking());

        $client->followRedirect();
        $client->submitForm('Valider la licence');
        $this->assertResponseRedirects($url);
        $license = $this->getEntityManager()->getRepository(License::class)->find(9);
        $this->assertSame([
            'validated' => 1,
        ], $license->getMarking());
    }

    public function testChainMedicalCertificateValidation()
    {
        $client = static::createClient();
        $url = '/admin/season/2/license/chain-medical-certificate-validation';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Valider le certificat médical');
        $this->assertResponseRedirects($url);
        $license = $this->getEntityManager()->getRepository(License::class)->find(9);
        $this->assertSame([
            'wait_payment_validation' => 1,
            'medical_certificate_validated' => 1,
        ], $license->getMarking());
    }
}
