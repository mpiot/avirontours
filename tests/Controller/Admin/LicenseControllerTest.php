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

use App\Entity\MedicalCertificate;
use App\Factory\LicenseFactory;
use App\Factory\SeasonCategoryFactory;
use App\Factory\SeasonFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LicenseControllerTest extends AppWebTestCase
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
        if (mb_strpos($url, '{season_id}')) {
            $season = SeasonFactory::createOne();
            $url = str_replace('{season_id}', (string) $season->getId(), $url);
        }

        if (mb_strpos($url, '{id}')) {
            $license = LicenseFactory::createOne([
                'seasonCategory' => SeasonCategoryFactory::createOne(['season' => SeasonFactory::createOne()]),
            ]);
            $url = str_replace('{id}', (string) $license->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider()
    {
        yield ['GET', '/admin/season/{season_id}/license/new'];
        yield ['POST', '/admin/season/{season_id}/license/new'];
        yield ['GET', '/admin/season/{season_id}/license/{id}/edit'];
        yield ['POST', '/admin/season/{season_id}/license/{id}/edit'];
        yield ['POST', '/admin/season/{season_id}/license/{id}/apply-transition'];
        yield ['DELETE', '/admin/season/{season_id}/license/{id}'];
        yield ['GET', '/admin/season/{season_id}/license/chain-medical-certificate-validation'];
    }

    public function testNewLicense(): void
    {
        SeasonFactory::createOne();
        $user = UserFactory::createOne();
        $season = SeasonFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $crawler = $client->request('GET', '/admin/season/'.$season->getId().'/license/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauver')->form([
            'license[user]' => $user->getId(),
            'license[seasonCategory]' => $season->getSeasonCategories()->first()->getId(),
            'license[logbookEntryLimit]' => 4,
            'license[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'license[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'license[medicalCertificate][date]' => '2020-05-01',
        ]);
        $form['license[medicalCertificate][file][file]']->upload(__DIR__.'/../../../src/DataFixtures/Files/medical-certificate.pdf');
        $client->submit($form);

        $this->assertResponseRedirects();

        $license = LicenseFactory::repository()->findOneBy([], ['id' => 'DESC']);

        $this->assertSame($user->getId(), $license->getUser()->getId());
        $this->assertSame($season->getSeasonCategories()->first()->getId(), $license->getSeasonCategory()->getId());
        $this->assertSame(4, $license->getLogbookEntryLimit());
        $this->assertSame(MedicalCertificate::TYPE_CERTIFICATE, $license->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificate::LEVEL_COMPETITION, $license->getMedicalCertificate()->getLevel());
        $this->assertSame('2020-05-01', $license->getMedicalCertificate()->getDate()->format('Y-m-d'));
    }

    public function testNewLicenseWithoutData(): void
    {
        $season = SeasonFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/season/'.$season->getId().'/license/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'license[medicalCertificate][date]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#license_user')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#license_seasonCategory')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_medicalCertificate_level')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_medicalCertificate_type')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_medicalCertificate_date')->parents()->filter('.invalid-feedback')->text());
        $this->assertCount(5, $crawler->filter('.invalid-feedback'));
        LicenseFactory::repository()->assert()->count(0);
    }

    public function testNewLicenseNonUnique(): void
    {
        $license = LicenseFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId().'/license/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'license[user]' => $license->getUser()->getId(),
            'license[seasonCategory]' => $license->getSeasonCategory()->getId(),
            'license[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'license[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'license[medicalCertificate][date]' => '2020-05-01',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Déjà inscrit pour cette saison.', $crawler->filter('.invalid-feedback')->text());
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        LicenseFactory::repository()->assert()->count(1);
    }

    public function testEditLicense(): void
    {
        $license = LicenseFactory::createOne();
        $seasonCategory = SeasonCategoryFactory::createOne(['season' => $license->getSeasonCategory()->getSeason()]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId().'/license/'.$license->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'license_edit[seasonCategory]' => $seasonCategory->getId(),
            'license_edit[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'license_edit[medicalCertificate][level]' => MedicalCertificate::LEVEL_PRACTICE,
            'license_edit[medicalCertificate][date]' => '2020-05-01',
        ]);

        $this->assertResponseRedirects();
        $this->assertSame($seasonCategory->getId(), $license->getSeasonCategory()->getId());
        $this->assertSame(MedicalCertificate::TYPE_CERTIFICATE, $license->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificate::LEVEL_PRACTICE, $license->getMedicalCertificate()->getLevel());
        $this->assertSame('2020-05-01', $license->getMedicalCertificate()->getDate()->format('Y-m-d'));
    }

    public function testDeleteLicense(): void
    {
        $license = LicenseFactory::createOne()->disableAutoRefresh();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId().'/license/'.$license->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());
        LicenseFactory::repository()->assert()->notExists($license);
    }

    public function testChainMedicalCertificateValidation(): void
    {
        $license = LicenseFactory::createOne(['marking' => ['wait_medical_certificate_validation' => 1, 'wait_payment_validation' => 1]]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId().'/license/chain-medical-certificate-validation');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Valider le certificat médical');

        $this->assertResponseRedirects('/admin/season/'.$license->getSeasonCategory()->getSeason()->getId().'/license/chain-medical-certificate-validation');
        $this->assertSame([
            'wait_payment_validation' => 1,
            'medical_certificate_validated' => 1,
        ], $license->getMarking());
    }

    public function testValidateMedicalCertificate(): void
    {
        $license = LicenseFactory::createOne(['marking' => null]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());

        $this->assertResponseIsSuccessful();

        $client->submitForm('Valider le certificat médical');

        $this->assertResponseRedirects('/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());
        $this->assertSame([
            'wait_payment_validation' => 1,
            'medical_certificate_validated' => 1,
        ], $license->getMarking());
    }

    public function testRejectMedicalCertificate(): void
    {
        $license = LicenseFactory::createOne(['marking' => null]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());

        $this->assertResponseIsSuccessful();

        $client->submitForm('Rejeter le certificat médical');

        $this->assertResponseRedirects('/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());
        $this->assertSame([
            'wait_payment_validation' => 1,
            'medical_certificate_rejected' => 1,
        ], $license->getMarking());
    }

    public function testUnrejectMedicalCertificate(): void
    {
        $license = LicenseFactory::createOne(['marking' => ['medical_certificate_rejected' => 1, 'wait_payment_validation' => 1]]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());

        $this->assertResponseIsSuccessful();

        $client->submitForm('Passer le certificat en attente de validation');

        $this->assertResponseRedirects('/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());
        $this->assertSame([
            'wait_payment_validation' => 1,
            'wait_medical_certificate_validation' => 1,
        ], $license->getMarking());
    }

    public function testValidatePayment(): void
    {
        $license = LicenseFactory::createOne(['marking' => null]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());

        $this->assertResponseIsSuccessful();

        $client->submitForm('Valider le paiement');

        $this->assertResponseRedirects('/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());
        $this->assertSame([
            'wait_medical_certificate_validation' => 1,
            'payment_validated' => 1,
        ], $license->getMarking());
    }

    public function testValidateLicense(): void
    {
        $license = LicenseFactory::createOne(['marking' => ['medical_certificate_validated' => 1, 'payment_validated' => 1]]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());

        $this->assertResponseIsSuccessful();

        $client->submitForm('Valider la licence');

        $this->assertResponseRedirects('/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());
        $this->assertSame([
            'validated' => 1,
        ], $license->getMarking());
    }
}
