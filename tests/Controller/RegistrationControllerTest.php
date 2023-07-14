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

use App\Entity\MedicalCertificate;
use App\Enum\LegalGuardianRole;
use App\Factory\LicenseFactory;
use App\Factory\PostalCodeFactory;
use App\Factory\SeasonFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RegistrationControllerTest extends AppWebTestCase
{
    public function testRegistration(): void
    {
        PostalCodeFactory::createOne([
            'postalCode' => '01000',
            'city' => 'One City',
        ]);
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();

        // Simulate AJAX call
        $crawler = $client->submitForm('S\'inscrire', [
            'registration[postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration[gender]' => 'm',
            'registration[firstName]' => 'John',
            'registration[lastName]' => 'Doe',
            'registration[email]' => 'john.doe@avirontours.fr',
            'registration[phoneNumber]' => '0102030405',
            'registration[plainPassword][first]' => 'engage',
            'registration[plainPassword][second]' => 'engage',
            'registration[nationality]' => 'FR',
            'registration[birthday]' => '2010-01-01',
            'registration[laneNumber]' => '100',
            'registration[laneType]' => 'Rue',
            'registration[laneName]' => 'du test',
            'registration[city]' => 'One City',
            'registration[clubEmailAllowed]' => 1,
            'registration[agreeSwim]' => 1,
            'registration[firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'registration[firstLegalGuardian][firstName]' => 'Gandalf',
            'registration[firstLegalGuardian][lastName]' => 'Le Blanc',
            'registration[firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'registration[firstLegalGuardian][phoneNumber]' => '0123456788',
            'registration[secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'registration[secondLegalGuardian][firstName]' => 'Galadriel',
            'registration[secondLegalGuardian][lastName]' => 'Artanis',
            'registration[secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'registration[secondLegalGuardian][phoneNumber]' => '0123456799',
            'registration[licenses][0][medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'registration[licenses][0][medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration[licenses][0][medicalCertificate][date]' => $date = (new \DateTime())->format('Y-m-d'),
            'registration[licenses][0][optionalInsurance]' => 1,
            'registration[licenses][0][federationEmailAllowed]' => 1,
        ]);
        $form['registration[licenses][0][medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
        $client->submit($form);

        $this->assertResponseRedirects();
        $this->assertQueuedEmailCount(1);
        $user = UserFactory::repository()->findOneBy(['email' => 'john.doe@avirontours.fr']);
        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame((new \DateTime())->format('Y-m-d'), $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame('m', $user->getGender());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('0102030405', $user->getPhoneNumber());
        $this->assertNotNull($user->getPassword());
        $this->assertSame('FR', $user->getNationality());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('100', $user->getLaneNumber());
        $this->assertSame('Rue', $user->getLaneType());
        $this->assertSame('Du Test', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertTrue($user->getClubEmailAllowed());
        $this->assertSame(LegalGuardianRole::Father, $user->getFirstLegalGuardian()->getRole());
        $this->assertSame('Gandalf', $user->getFirstLegalGuardian()->getFirstName());
        $this->assertSame('Le Blanc', $user->getFirstLegalGuardian()->getLastName());
        $this->assertSame('g.le-blanc@avirontours.fr', $user->getFirstLegalGuardian()->getEmail());
        $this->assertSame('0123456788', $user->getFirstLegalGuardian()->getPhoneNumber());
        $this->assertSame(LegalGuardianRole::Mother, $user->getSecondLegalGuardian()->getRole());
        $this->assertSame('Galadriel', $user->getSecondLegalGuardian()->getFirstName());
        $this->assertSame('Artanis', $user->getSecondLegalGuardian()->getLastName());
        $this->assertSame('g.artanis@avirontours.fr', $user->getSecondLegalGuardian()->getEmail());
        $this->assertSame('0123456799', $user->getSecondLegalGuardian()->getPhoneNumber());
        $this->assertCount(1, $user->getLicenses());
        $this->assertSame($season->getSeasonCategories()->first(), $user->getLicenses()->first()->getSeasonCategory());
        $this->assertTrue($user->getLicenses()->first()->getFederationEmailAllowed());
        $this->assertTrue($user->getLicenses()->first()->getOptionalInsurance());
        $this->assertNotNull($user->getLicenses()->first()->getMedicalCertificate());
        $this->assertSame(MedicalCertificate::TYPE_CERTIFICATE, $user->getLicenses()->first()->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificate::LEVEL_COMPETITION, $user->getLicenses()->first()->getMedicalCertificate()->getLevel());
        $this->assertSame($date, $user->getLicenses()->first()->getMedicalCertificate()->getDate()->format('Y-m-d'));
        $this->assertNotNull($user->getLicenses()->first()->getMedicalCertificate()->getFile());
        UserFactory::repository()->assert()->count(1);
        LicenseFactory::repository()->assert()->count(1);
    }

    public function testRegistrationWithoutData(): void
    {
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('S\'inscrire', [
            'registration[firstName]' => '',
            'registration[lastName]' => '',
            'registration[email]' => '',
            'registration[phoneNumber]' => '',
            'registration[plainPassword][first]' => '',
            'registration[plainPassword][second]' => '',
            'registration[nationality]' => '',
            'registration[birthday]' => '',
            'registration[laneNumber]' => '',
            'registration[laneType]' => '',
            'registration[laneName]' => '',
            'registration[postalCode]' => '',
            'registration[city]' => '',
            'registration[firstLegalGuardian][role]' => '',
            'registration[firstLegalGuardian][firstName]' => '',
            'registration[firstLegalGuardian][lastName]' => '',
            'registration[firstLegalGuardian][email]' => '',
            'registration[firstLegalGuardian][phoneNumber]' => '',
            'registration[secondLegalGuardian][role]' => '',
            'registration[secondLegalGuardian][firstName]' => '',
            'registration[secondLegalGuardian][lastName]' => '',
            'registration[secondLegalGuardian][email]' => '',
            'registration[secondLegalGuardian][phoneNumber]' => '',
            'registration[licenses][0][medicalCertificate][date]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_gender')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_firstName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_lastName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_email')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_plainPassword_first')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_nationality')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_birthday')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_laneNumber')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#registration_laneType')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_laneName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_postalCode')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_city')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_licenses_0_medicalCertificate_type')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_licenses_0_medicalCertificate_level')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_licenses_0_medicalCertificate_date')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('input#registration_licenses_0_medicalCertificate_file_file')->closest('fieldset')->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Vous devez savoir nager 25m avec un départ plongé pour vous inscrire.', $crawler->filter('#registration_agreeSwim')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(17, $crawler->filter('.invalid-feedback'));
        UserFactory::repository()->assert()->count(0);
        LicenseFactory::repository()->assert()->count(0);
    }

    public function testRegistrationWithoutLegalGuardianForUnderEighteen(): void
    {
        PostalCodeFactory::createOne([
            'postalCode' => '01000',
            'city' => 'One City',
        ]);
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();

        // Simulate AJAX call
        $crawler = $client->submitForm('S\'inscrire', [
            'registration[postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration[gender]' => 'm',
            'registration[firstName]' => 'John',
            'registration[lastName]' => 'Doe',
            'registration[email]' => 'john.doe@avirontours.fr',
            'registration[phoneNumber]' => '0102030405',
            'registration[plainPassword][first]' => 'engage',
            'registration[plainPassword][second]' => 'engage',
            'registration[nationality]' => 'FR',
            'registration[birthday]' => SeasonFactory::faker()->dateTimeBetween('-17 years', '-11 years')->format('Y-m-d'),
            'registration[laneNumber]' => '100',
            'registration[laneType]' => 'Rue',
            'registration[laneName]' => 'du test',
            'registration[city]' => 'One City',
            'registration[clubEmailAllowed]' => 1,
            'registration[agreeSwim]' => 1,
            'registration[firstLegalGuardian][role]' => '',
            'registration[firstLegalGuardian][firstName]' => '',
            'registration[firstLegalGuardian][lastName]' => '',
            'registration[firstLegalGuardian][email]' => '',
            'registration[firstLegalGuardian][phoneNumber]' => '',
            'registration[secondLegalGuardian][role]' => '',
            'registration[secondLegalGuardian][firstName]' => '',
            'registration[secondLegalGuardian][lastName]' => '',
            'registration[secondLegalGuardian][email]' => '',
            'registration[secondLegalGuardian][phoneNumber]' => '',
            'registration[licenses][0][medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'registration[licenses][0][medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration[licenses][0][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration[licenses][0][optionalInsurance]' => 1,
            'registration[licenses][0][federationEmailAllowed]' => 1,
        ]);
        $form['registration[licenses][0][medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Le membre est mineur, merci de renseigner un représentant légal.', $crawler->filter('form > div.alert.alert-danger')->text());
        $this->assertCount(1, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(0, $crawler->filter('.invalid-feedback'));
        UserFactory::repository()->assert()->count(0);
        LicenseFactory::repository()->assert()->count(0);
    }

    public function testRegistrationWithoutLegalGuardianOverEighteen(): void
    {
        PostalCodeFactory::createOne([
            'postalCode' => '01000',
            'city' => 'One City',
        ]);
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();

        // Simulate AJAX call
        $crawler = $client->submitForm('S\'inscrire', [
            'registration[postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration[gender]' => 'm',
            'registration[firstName]' => 'John',
            'registration[lastName]' => 'Doe',
            'registration[email]' => 'john.doe@avirontours.fr',
            'registration[phoneNumber]' => '0102030405',
            'registration[plainPassword][first]' => 'engage',
            'registration[plainPassword][second]' => 'engage',
            'registration[nationality]' => 'FR',
            'registration[birthday]' => SeasonFactory::faker()->dateTimeBetween('-80 years', '-20 years')->format('Y-m-d'),
            'registration[laneNumber]' => '100',
            'registration[laneType]' => 'Rue',
            'registration[laneName]' => 'du test',
            'registration[city]' => 'One City',
            'registration[clubEmailAllowed]' => 1,
            'registration[agreeSwim]' => 1,
            'registration[firstLegalGuardian][role]' => '',
            'registration[firstLegalGuardian][firstName]' => '',
            'registration[firstLegalGuardian][lastName]' => '',
            'registration[firstLegalGuardian][email]' => '',
            'registration[firstLegalGuardian][phoneNumber]' => '',
            'registration[secondLegalGuardian][role]' => '',
            'registration[secondLegalGuardian][firstName]' => '',
            'registration[secondLegalGuardian][lastName]' => '',
            'registration[secondLegalGuardian][email]' => '',
            'registration[secondLegalGuardian][phoneNumber]' => '',
            'registration[licenses][0][medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'registration[licenses][0][medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration[licenses][0][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration[licenses][0][optionalInsurance]' => 1,
            'registration[licenses][0][federationEmailAllowed]' => 1,
        ]);
        $form['registration[licenses][0][medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
        $client->submit($form);

        $this->assertResponseRedirects();
        UserFactory::repository()->assert()->count(1);
        LicenseFactory::repository()->assert()->count(1);
    }

    public function testRegistrationTwice(): void
    {
        PostalCodeFactory::createOne([
            'postalCode' => '01000',
            'city' => 'One City',
        ]);
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();
        $license = LicenseFactory::createOne(['seasonCategory' => $season->getSeasonCategories()->first()]);

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$license->getSeasonCategory()->getSlug());

        $this->assertResponseIsSuccessful();

        // Simulate AJAX call
        $crawler = $client->submitForm('S\'inscrire', [
            'registration[postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration[gender]' => 'm',
            'registration[firstName]' => $license->getUser()->getFirstName(),
            'registration[lastName]' => $license->getUser()->getLastName(),
            'registration[email]' => 'john.doe@avirontours.fr',
            'registration[phoneNumber]' => '0102030405',
            'registration[plainPassword][first]' => 'engage',
            'registration[plainPassword][second]' => 'engage',
            'registration[nationality]' => 'FR',
            'registration[birthday]' => '2010-01-01',
            'registration[laneNumber]' => '100',
            'registration[laneType]' => 'Rue',
            'registration[laneName]' => 'du test',
            'registration[city]' => 'One City',
            'registration[clubEmailAllowed]' => 1,
            'registration[agreeSwim]' => 1,
            'registration[firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'registration[firstLegalGuardian][firstName]' => 'Gandalf',
            'registration[firstLegalGuardian][lastName]' => 'Le Blanc',
            'registration[firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'registration[firstLegalGuardian][phoneNumber]' => '0123456788',
            'registration[secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'registration[secondLegalGuardian][firstName]' => 'Galadriel',
            'registration[secondLegalGuardian][lastName]' => 'Artanis',
            'registration[secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'registration[secondLegalGuardian][phoneNumber]' => '0123456799',
            'registration[licenses][0][medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'registration[licenses][0][medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration[licenses][0][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration[licenses][0][optionalInsurance]' => 1,
            'registration[licenses][0][federationEmailAllowed]' => 1,
        ]);
        $form['registration[licenses][0][medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Un compte existe déjà avec ce nom et prénom.', $crawler->filter('#registration_firstName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        UserFactory::repository()->assert()->count(1);
        LicenseFactory::repository()->assert()->count(1);
    }

    public function testNonEnabledRegistration(): void
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testNonDisplayedCategoryRegistration(): void
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesNotDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testRegistrationAsLogInUser(): void
    {
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseRedirects('/profile');
    }

    public function testRenew(): void
    {
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $user = $this->logIn($client, 'ROLE_USER');
        $crawler = $client->request('GET', '/renew/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('S\'inscrire')->form([
            'renew[medicalCertificate][type]' => MedicalCertificate::TYPE_ATTESTATION,
            'renew[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'renew[medicalCertificate][date]' => $date = (new \DateTime())->format('Y-m-d'),
            'renew[federationEmailAllowed]' => 1,
            'renew[optionalInsurance]' => 1,
            'renew[agreeSwim]' => 1,
        ]);

        $form['renew[medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
        $client->submit($form);

        $this->assertResponseRedirects();
        $this->assertQueuedEmailCount(1);
        $this->assertCount(1, $user->getLicenses());
        $this->assertSame(MedicalCertificate::TYPE_ATTESTATION, $user->getLicenses()->last()->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificate::LEVEL_COMPETITION, $user->getLicenses()->last()->getMedicalCertificate()->getLevel());
        $this->assertSame($date, $user->getLicenses()->last()->getMedicalCertificate()->getdate()->format('Y-m-d'));
        $this->assertTrue($user->getLicenses()->last()->getFederationEmailAllowed());
        $this->assertTrue($user->getLicenses()->last()->getOptionalInsurance());
    }

    public function testNonDisplayedCategoryRenew(): void
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesNotDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/renew/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testNonEnabledRenew(): void
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/renew/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testRenewAsAnonymousUser(): void
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/renew/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseRedirects('/login');
    }
}
