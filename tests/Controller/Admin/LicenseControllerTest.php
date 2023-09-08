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
use App\Enum\PaymentMethod;
use App\Factory\LicenseFactory;
use App\Factory\LicensePaymentFactory;
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

    /**
     * @dataProvider adminUrlProvider
     */
    public function testAccessDeniedForSeasonModerator($method, $url): void
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
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider(): \Generator
    {
        yield ['GET', '/admin/season/{season_id}/license/new'];
        yield ['POST', '/admin/season/{season_id}/license/new'];
        yield ['GET', '/admin/season/{season_id}/license/{id}/edit'];
        yield ['POST', '/admin/season/{season_id}/license/{id}/edit'];
        yield ['GET', '/admin/season/{season_id}/license/{id}/validate-payment'];
        yield ['POST', '/admin/season/{season_id}/license/{id}/validate-payment'];
        yield ['GET', '/admin/season/{season_id}/license/chain-medical-certificate-validation'];
        yield ['GET', '/admin/season/{season_id}/license/{id}/apply-transition/validate_medical_certificate'];
        yield ['GET', '/admin/season/{season_id}/license/{id}/apply-transition/reject_medical_certificate'];
        yield ['GET', '/admin/season/{season_id}/license/{id}/apply-transition/unreject_medical_certificate'];
        yield ['GET', '/admin/season/{season_id}/license/{id}/apply-transition/validate_license'];
    }

    public function adminUrlProvider(): \Generator
    {
        yield ['POST', '/admin/season/{season_id}/license/{id}'];
    }

    public function testNewLicense(): void
    {
        SeasonFactory::createOne();
        $user = UserFactory::createOne();
        $season = SeasonFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $crawler = $client->request('GET', '/admin/season/'.$season->getId().'/license/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauver')->form([
            'license[user]' => $user->getId(),
            'license[seasonCategory]' => $season->getSeasonCategories()->first()->getId(),
            'license[logbookEntryLimit]' => 4,
            'license[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'license[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'license[medicalCertificate][date]' => $date = date('Y-m-d'),
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
        $this->assertSame($date, $license->getMedicalCertificate()->getDate()->format('Y-m-d'));
    }

    public function testNewLicenseWithoutData(): void
    {
        $season = SeasonFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $client->request('GET', '/admin/season/'.$season->getId().'/license/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'license[medicalCertificate][date]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#license_user')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#license_seasonCategory')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_medicalCertificate_level')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_medicalCertificate_type')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_medicalCertificate_date')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(5, $crawler->filter('.invalid-feedback'));
        LicenseFactory::repository()->assert()->count(0);
    }

    public function testNewLicenseNonUnique(): void
    {
        $license = LicenseFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId().'/license/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'license[user]' => $license->getUser()->getId(),
            'license[seasonCategory]' => $license->getSeasonCategory()->getId(),
            'license[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'license[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'license[medicalCertificate][date]' => date('Y-m-d'),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Déjà inscrit pour cette saison.', $crawler->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        LicenseFactory::repository()->assert()->count(1);
    }

    public function testEditLicense(): void
    {
        $license = LicenseFactory::createOne();
        $seasonCategory = SeasonCategoryFactory::createOne(['season' => $license->getSeasonCategory()->getSeason()]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId().'/license/'.$license->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'license_edit[seasonCategory]' => $seasonCategory->getId(),
            'license_edit[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'license_edit[medicalCertificate][level]' => MedicalCertificate::LEVEL_PRACTICE,
            'license_edit[medicalCertificate][date]' => $date = date('Y-m-d'),
        ]);

        $this->assertResponseRedirects();
        $this->assertSame($seasonCategory->getId(), $license->getSeasonCategory()->getId());
        $this->assertSame(MedicalCertificate::TYPE_CERTIFICATE, $license->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificate::LEVEL_PRACTICE, $license->getMedicalCertificate()->getLevel());
        $this->assertSame($date, $license->getMedicalCertificate()->getDate()->format('Y-m-d'));
    }

    public function testValidatePayment(): void
    {
        $license = LicenseFactory::createOne(['marking' => []]);
        $seasonCategory = SeasonCategoryFactory::createOne(['season' => $license->getSeasonCategory()->getSeason()]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $crawler = $client->request('GET', "/admin/season/{$seasonCategory->getSeason()->getId()}/license/{$license->getId()}/validate-payment");

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauver')->form();
        $values = $form->getPhpValues();
        $values['license_payment']['payments'][0]['method'] = PaymentMethod::Check->value;
        $values['license_payment']['payments'][0]['amount'] = 240;
        $values['license_payment']['payments'][0]['checkNumber'] = '1234567';
        $values['license_payment']['payments'][0]['checkDate'] = '2023-09-01';
        $values['license_payment']['payments'][1]['method'] = PaymentMethod::VacationCheck->value;
        $values['license_payment']['payments'][1]['amount'] = 120;
        $values['license_payment']['payments'][1]['checkNumber'] = '1234568';
        $values['license_payment']['payments'][2]['method'] = PaymentMethod::Cash->value;
        $values['license_payment']['payments'][2]['amount'] = 100;
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseRedirects();
        $this->assertSame(['wait_medical_certificate_validation' => 1, 'payment_validated' => 1], $license->getMarking());
        $this->assertNotNull($license->getPayedAt());
        $this->assertCount(3, $license->getPayments());
        $this->assertSame(PaymentMethod::Check, $license->getPayments()->get(0)->getMethod());
        $this->assertSame(24000, $license->getPayments()->get(0)->getAmount());
        $this->assertSame('1234567', $license->getPayments()->get(0)->getCheckNumber());
        $this->assertSame('2023-09-01', $license->getPayments()->get(0)->getCheckDate()->format('Y-m-d'));
        $this->assertSame(PaymentMethod::VacationCheck, $license->getPayments()->get(1)->getMethod());
        $this->assertSame(12000, $license->getPayments()->get(1)->getAmount());
        $this->assertSame('1234568', $license->getPayments()->get(1)->getCheckNumber());
        $this->assertNull($license->getPayments()->get(1)->getCheckDate());
        $this->assertSame(PaymentMethod::Cash, $license->getPayments()->get(2)->getMethod());
        $this->assertSame(10000, $license->getPayments()->get(2)->getAmount());
        $this->assertNull($license->getPayments()->get(2)->getCheckNumber());
        $this->assertNull($license->getPayments()->get(2)->getCheckDate());
    }

    public function testValidateCheckPaymentWithoutCheckNumber(): void
    {
        $license = LicenseFactory::createOne(['marking' => []]);
        $seasonCategory = SeasonCategoryFactory::createOne(['season' => $license->getSeasonCategory()->getSeason()]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $crawler = $client->request('GET', "/admin/season/{$seasonCategory->getSeason()->getId()}/license/{$license->getId()}/validate-payment");

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauver')->form();
        $values = $form->getPhpValues();
        $values['license_payment']['payments'][0]['method'] = PaymentMethod::Check->value;
        $values['license_payment']['payments'][0]['amount'] = 240;
        $values['license_payment']['payments'][0]['checkNumber'] = '';
        $values['license_payment']['payments'][1]['method'] = PaymentMethod::VacationCheck->value;
        $values['license_payment']['payments'][1]['amount'] = 120;
        $values['license_payment']['payments'][1]['checkNumber'] = '';
        $values['license_payment']['payments'][2]['method'] = PaymentMethod::Cash->value;
        $values['license_payment']['payments'][2]['amount'] = 100;
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_payment_payments_0_checkNumber')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_payment_payments_0_checkDate')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_payment_payments_1_checkNumber')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(3, $crawler->filter('.invalid-feedback'));
        LicensePaymentFactory::repository()->assert()->count(0);
    }

    public function testValidatePaymentWithoutData(): void
    {
        $license = LicenseFactory::createOne(['marking' => []]);
        $seasonCategory = SeasonCategoryFactory::createOne(['season' => $license->getSeasonCategory()->getSeason()]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $client->request('GET', "/admin/season/{$seasonCategory->getSeason()->getId()}/license/{$license->getId()}/validate-payment");

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette collection doit contenir 1 élément ou plus.', $crawler->filter('#license_payment_payments')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        LicensePaymentFactory::repository()->assert()->count(0);
    }

    public function testValidatePaymentWithEmptyData(): void
    {
        $license = LicenseFactory::createOne(['marking' => []]);
        $seasonCategory = SeasonCategoryFactory::createOne(['season' => $license->getSeasonCategory()->getSeason()]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $crawler = $client->request('GET', "/admin/season/{$seasonCategory->getSeason()->getId()}/license/{$license->getId()}/validate-payment");

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauver')->form();
        $values = $form->getPhpValues();
        $values['license_payment']['payments'][0]['method'] = '';
        $values['license_payment']['payments'][0]['amount'] = '';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#license_payment_payments_0_method')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#license_payment_payments_0_amount')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(2, $crawler->filter('.invalid-feedback'));
        LicensePaymentFactory::repository()->assert()->count(0);
    }

    public function testChainMedicalCertificateValidationWithEmptyArray(): void
    {
        $license = LicenseFactory::createOne(['marking' => []]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId().'/license/chain-medical-certificate-validation');

        $this->assertResponseIsSuccessful();

        $client->clickLink('Valider le certificat médical');

        $this->assertResponseRedirects("http://localhost/admin/season/{$license->getSeasonCategory()->getSeason()->getId()}/license/chain-medical-certificate-validation");
        $this->assertSame([
            'wait_payment_validation' => 1,
            'medical_certificate_validated' => 1,
        ], $license->getMarking());
    }

    public function testChainMedicalCertificateValidationWithMarkingFilled(): void
    {
        $license = LicenseFactory::createOne(['marking' => ['wait_medical_certificate_validation' => 1, 'wait_payment_validation' => 1]]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId().'/license/chain-medical-certificate-validation');

        $this->assertResponseIsSuccessful();

        $client->clickLink('Valider le certificat médical');

        $this->assertResponseRedirects("http://localhost/admin/season/{$license->getSeasonCategory()->getSeason()->getId()}/license/chain-medical-certificate-validation");
        $this->assertSame([
            'wait_payment_validation' => 1,
            'medical_certificate_validated' => 1,
        ], $license->getMarking());
    }

    public function testValidateMedicalCertificate(): void
    {
        $license = LicenseFactory::createOne(['marking' => []]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());

        $this->assertResponseIsSuccessful();

        $client->clickLink('Valider le certificat médical');

        $this->assertResponseRedirects("http://localhost/admin/season/{$license->getSeasonCategory()->getSeason()->getId()}");
        $this->assertSame([
            'wait_payment_validation' => 1,
            'medical_certificate_validated' => 1,
        ], $license->getMarking());
    }

    public function testRejectMedicalCertificate(): void
    {
        $license = LicenseFactory::createOne(['marking' => []]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());

        $this->assertResponseIsSuccessful();

        $client->clickLink('Rejeter le certificat médical');

        $this->assertResponseRedirects("http://localhost/admin/season/{$license->getSeasonCategory()->getSeason()->getId()}");
        $this->assertQueuedEmailCount(1);
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
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());

        $this->assertResponseIsSuccessful();

        $client->clickLink('Passer le certificat en attente de validation');

        $this->assertResponseRedirects("http://localhost/admin/season/{$license->getSeasonCategory()->getSeason()->getId()}");
        $this->assertSame([
            'wait_payment_validation' => 1,
            'wait_medical_certificate_validation' => 1,
        ], $license->getMarking());
    }

    public function testValidateLicense(): void
    {
        $license = LicenseFactory::createOne(['marking' => ['medical_certificate_validated' => 1, 'payment_validated' => 1]]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_ADMIN');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());

        $this->assertResponseIsSuccessful();

        $client->clickLink('Valider la licence');

        $this->assertResponseRedirects("http://localhost/admin/season/{$license->getSeasonCategory()->getSeason()->getId()}");
        $this->assertSame([
            'validated' => 1,
        ], $license->getMarking());
    }

    public function testValidateLicenseAsModerator(): void
    {
        $license = LicenseFactory::createOne(['marking' => ['medical_certificate_validated' => 1, 'payment_validated' => 1]]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_MODERATOR');
        $crawler = $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId());

        $this->assertResponseIsSuccessful();

        $this->assertCount(0, $crawler->selectLink('Valider la licence'));
    }

    public function testDeleteLicense(): void
    {
        $license = LicenseFactory::createOne()->disableAutoRefresh();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SEASON_ADMIN');
        $client->request('GET', '/admin/season/'.$license->getSeasonCategory()->getSeason()->getId().'/license/'.$license->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects("/admin/season/{$license->getSeasonCategory()->getSeason()->getId()}");
        LicenseFactory::repository()->assert()->notExists($license);
    }
}
