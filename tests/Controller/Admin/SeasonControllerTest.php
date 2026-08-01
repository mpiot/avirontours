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

use App\Entity\SeasonCategory;
use App\Factory\LicenseFactory;
use App\Factory\LicensePaymentFactory;
use App\Factory\MedicalCertificateFactory;
use App\Factory\SeasonCategoryFactory;
use App\Factory\SeasonFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SeasonControllerTest extends AppWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('urlProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('paymentAdminUrlProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('adminUrlProvider')]
    public function testAccessDeniedForAnonymousUser(string $method, string $url): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('urlProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('paymentAdminUrlProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('adminUrlProvider')]
    public function testAccessDeniedForRegularUser(string $method, string $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $season = SeasonFactory::createOne();
            $url = str_replace('{id}', (string) $season->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('adminUrlProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('paymentAdminUrlProvider')]
    public function testAccessDeniedForMedicalCertificateAdmin(string $method, string $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $season = SeasonFactory::createOne();
            $url = str_replace('{id}', (string) $season->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('adminUrlProvider')]
    public function testAccessDeniedForPaymentsAdmin(string $method, string $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $season = SeasonFactory::createOne();
            $url = str_replace('{id}', (string) $season->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_PAYMENTS_ADMIN');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testIndexSeasons(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_ADMIN');
        $client->request('GET', '/admin/season');

        $this->assertResponseIsSuccessful();

        $this->createAndLogin($client, 'ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN');
        $client->request('GET', '/admin/season');

        $this->assertResponseIsSuccessful();

        $this->createAndLogin($client, 'ROLE_SEASON_PAYMENTS_ADMIN');
        $client->request('GET', '/admin/season');

        $this->assertResponseIsSuccessful();
    }

    public function testShowSeason(): void
    {
        $season = SeasonFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_ADMIN');
        $client->request('GET', '/admin/season/'.$season->getId());

        $this->assertResponseIsSuccessful();

        $this->createAndLogin($client, 'ROLE_SEASON_MEDICAL_CERTIFICATE_ADMIN');
        $client->request('GET', '/admin/season/'.$season->getId());

        $this->assertResponseIsSuccessful();

        $this->createAndLogin($client, 'ROLE_SEASON_PAYMENTS_ADMIN');
        $client->request('GET', '/admin/season/'.$season->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testNewSeason(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_ADMIN');
        $crawler = $client->request('GET', '/admin/season/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauver')->form([
            'season[name]' => 2030,
            'season[active]' => 1,
            'season[subscriptionEnabled]' => 1,
        ]);
        $values = $form->getPhpValues();
        $values['season']['seasonCategories'][0]['name'] = 'My category name';
        $values['season']['seasonCategories'][0]['price'] = 99.32;
        $values['season']['seasonCategories'][0]['licenseType'] = SeasonCategory::LICENSE_TYPE_ANNUAL;
        $values['season']['seasonCategories'][0]['description'] = 'My category description';
        $values['season']['seasonCategories'][0]['displayed'] = true;
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseRedirects();

        $season = SeasonFactory::repository()->findOneBy(['name' => 2030]);

        $this->assertTrue($season->getActive());
        $this->assertTrue($season->getSubscriptionEnabled());
        $this->assertCount(1, $season->getSeasonCategories());
        $this->assertSame('My category name', $season->getSeasonCategories()->first()->getName());
        $this->assertSame(99.32, $season->getSeasonCategories()->first()->getPrice());
        $this->assertSame(SeasonCategory::LICENSE_TYPE_ANNUAL, $season->getSeasonCategories()->first()->getLicenseType());
        $this->assertSame('My category description', $season->getSeasonCategories()->first()->getdescription());
        $this->assertTrue($season->getSeasonCategories()->first()->getDisplayed());
    }

    public function testNewSeasonWithoutData(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_ADMIN');
        $client->request('GET', '/admin/season/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'season[name]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette collection doit contenir 1 élément ou plus.', $crawler->filter('#season_seasonCategories')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#season_name')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(2, $crawler->filter('.invalid-feedback'));
        SeasonFactory::repository()->assert()->count(0);
    }

    public function testEditSeason(): void
    {
        $season = SeasonFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_ADMIN');
        $client->request('GET', '/admin/season/'.$season->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'season[name]' => 2030,
        ]);

        $this->assertResponseRedirects();
        $this->assertSame(2030, $season->getName());
    }

    public function testDeleteSeason(): void
    {
        $season = SeasonFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_ADMIN');
        $client->request('GET', '/admin/season/'.$season->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/admin/season');
        SeasonFactory::repository()->assert()->notExists($season);
    }

    public function testDeleteSeasonWithLicenses(): void
    {
        $season = SeasonFactory::createOne();
        $seasonCategory = SeasonCategoryFactory::createOne(['season' => $season]);
        $medicalCertificate = MedicalCertificateFactory::createOne();
        $license = LicenseFactory::createOne([
            'seasonCategory' => $seasonCategory,
            'medicalCertificate' => $medicalCertificate,
        ]);
        $payment = LicensePaymentFactory::createOne(['license' => $license]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_ADMIN');
        $client->request('GET', '/admin/season/'.$season->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/admin/season');
        SeasonFactory::repository()->assert()->notExists($season);
        SeasonCategoryFactory::repository()->assert()->notExists($seasonCategory);
        MedicalCertificateFactory::repository()->assert()->notExists($medicalCertificate);
        LicenseFactory::repository()->assert()->notExists($license);
        LicensePaymentFactory::repository()->assert()->notExists($payment);
    }

    public function testExportSeasonContacts(): void
    {
        $season = SeasonFactory::createOne();
        LicenseFactory::new()->withValidLicense()->create([
            'seasonCategory' => $season->getSeasonCategories()->first(),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_ADMIN');
        $client->request('GET', '/admin/season/'.$season->getId().'/export/contact');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/csv; charset=UTF-8');
    }

    public function testExportSeasonPayments(): void
    {
        $season = SeasonFactory::createOne();
        LicenseFactory::new()->withPayments()->withValidLicense()->create([
            'seasonCategory' => $season->getSeasonCategories()->first(),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_PAYMENTS_ADMIN');
        $client->request('GET', '/admin/season/'.$season->getId().'/export/payment');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/csv; charset=UTF-8');
    }

    public function testExportSeasonLicenses(): void
    {
        $season = SeasonFactory::createOne();
        LicenseFactory::createOne([
            'seasonCategory' => $season->getSeasonCategories()->first(),
            'marking' => ['medical_certificate_validated' => 1, 'payment_validated' => 1],
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SEASON_ADMIN');
        $client->request('GET', '/admin/season/'.$season->getId().'/export/license');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/csv; charset=UTF-8');
    }

    public static function urlProvider(): \Generator
    {
        yield ['GET', '/admin/season'];
        yield ['GET', '/admin/season/{id}'];
    }

    public static function paymentAdminUrlProvider(): \Generator
    {
        yield ['GET', '/admin/season/{id}/export/payment'];
    }

    public static function adminUrlProvider(): \Generator
    {
        yield ['GET', '/admin/season/new'];
        yield ['POST', '/admin/season/new'];
        yield ['GET', '/admin/season/{id}/edit'];
        yield ['POST', '/admin/season/{id}/edit'];
        yield ['GET', '/admin/season/{id}/export/contact'];
        yield ['GET', '/admin/season/{id}/export/license'];
    }
}
