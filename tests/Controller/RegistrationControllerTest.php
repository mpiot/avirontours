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

use App\Enum\Gender;
use App\Enum\LegalGuardianRole;
use App\Enum\MedicalCertificateLevel;
use App\Enum\MedicalCertificateType;
use App\Factory\LicenseFactory;
use App\Factory\MedicalCertificateFactory;
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
            'registration[user][postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration[user][gender]' => Gender::Male->value,
            'registration[user][firstName]' => 'John',
            'registration[user][lastName]' => 'Doe',
            'registration[user][email]' => 'john.doe@avirontours.fr',
            'registration[user][phoneNumber]' => '0102030405',
            'registration[user][plainPassword][first]' => 'engage',
            'registration[user][plainPassword][second]' => 'engage',
            'registration[user][nationality]' => 'FR',
            'registration[user][birthday]' => '2010-01-01',
            'registration[user][laneNumber]' => '100',
            'registration[user][laneType]' => 'Rue',
            'registration[user][laneName]' => 'du test',
            'registration[user][city]' => 'One City',
            'registration[user][clubEmailAllowed]' => 1,
            'registration[agreeSwim]' => 1,
            'registration[agreeRulesAndRegulations]' => 1,
            'registration[user][firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'registration[user][firstLegalGuardian][firstName]' => 'Gandalf',
            'registration[user][firstLegalGuardian][lastName]' => 'Le Blanc',
            'registration[user][firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'registration[user][firstLegalGuardian][phoneNumber]' => '0123456788',
            'registration[user][secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'registration[user][secondLegalGuardian][firstName]' => 'Galadriel',
            'registration[user][secondLegalGuardian][lastName]' => 'Artanis',
            'registration[user][secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'registration[user][secondLegalGuardian][phoneNumber]' => '0123456799',
            'registration[license][medicalCertificate][type]' => MedicalCertificateType::Certificate->value,
            'registration[license][medicalCertificate][level]' => MedicalCertificateLevel::Competition->value,
            'registration[license][medicalCertificate][date]' => $date = (new \DateTime())->format('Y-m-d'),
            'registration[license][optionalInsurance]' => 1,
            'registration[license][federationEmailAllowed]' => 1,
        ]);
        $form['registration[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $client->submit($form);

        $this->assertResponseRedirects();
        $this->assertQueuedEmailCount(1);
        $user = UserFactory::repository()->findOneBy(['email' => 'john.doe@avirontours.fr']);
        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame((new \DateTime())->format('Y-m-d'), $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame(Gender::Male, $user->getGender());
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
        $this->assertSame(MedicalCertificateType::Certificate, $user->getLicenses()->first()->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificateLevel::Competition, $user->getLicenses()->first()->getMedicalCertificate()->getLevel());
        $this->assertSame($date, $user->getLicenses()->first()->getMedicalCertificate()->getDate()->format('Y-m-d'));
        $this->assertNotNull($user->getLicenses()->first()->getMedicalCertificate()->getUploadedFile());
        UserFactory::repository()->assert()->count(1);
        LicenseFactory::repository()->assert()->count(1);
        MedicalCertificateFactory::repository()->assert()->count(1);
    }

    public function testRegistrationWithoutData(): void
    {
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('S\'inscrire', [
            'registration[user][firstName]' => '',
            'registration[user][lastName]' => '',
            'registration[user][email]' => '',
            'registration[user][phoneNumber]' => '',
            'registration[user][plainPassword][first]' => '',
            'registration[user][plainPassword][second]' => '',
            'registration[user][nationality]' => '',
            'registration[user][birthday]' => '',
            'registration[user][laneNumber]' => '',
            'registration[user][laneType]' => '',
            'registration[user][laneName]' => '',
            'registration[user][postalCode]' => '',
            'registration[user][city]' => '',
            'registration[user][firstLegalGuardian][role]' => '',
            'registration[user][firstLegalGuardian][firstName]' => '',
            'registration[user][firstLegalGuardian][lastName]' => '',
            'registration[user][firstLegalGuardian][email]' => '',
            'registration[user][firstLegalGuardian][phoneNumber]' => '',
            'registration[user][secondLegalGuardian][role]' => '',
            'registration[user][secondLegalGuardian][firstName]' => '',
            'registration[user][secondLegalGuardian][lastName]' => '',
            'registration[user][secondLegalGuardian][email]' => '',
            'registration[user][secondLegalGuardian][phoneNumber]' => '',
            'registration[license][medicalCertificate][date]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_user_gender')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_user_firstName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_user_lastName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_user_email')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_user_plainPassword_first')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_user_nationality')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_user_birthday')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_user_laneNumber')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#registration_user_laneType')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_user_laneName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_user_postalCode')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_user_city')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_license_medicalCertificate_type')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_license_medicalCertificate_level')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_license_medicalCertificate_date')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('input#registration_license_medicalCertificate_file')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Vous devez savoir nager 25m avec un départ plongé pour vous inscrire.', $crawler->filter('#registration_agreeSwim')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Vous devez attester avoir avoir lu le règlement intérieur et l\'accepter dans son intégralité pour vous inscrire.', $crawler->filter('#registration_agreeRulesAndRegulations')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(18, $crawler->filter('.invalid-feedback'));
        UserFactory::repository()->assert()->count(0);
        LicenseFactory::repository()->assert()->count(0);
        MedicalCertificateFactory::repository()->assert()->count(0);
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
            'registration[user][postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration[user][gender]' => Gender::Male->value,
            'registration[user][firstName]' => 'John',
            'registration[user][lastName]' => 'Doe',
            'registration[user][email]' => 'john.doe@avirontours.fr',
            'registration[user][phoneNumber]' => '0102030405',
            'registration[user][plainPassword][first]' => 'engage',
            'registration[user][plainPassword][second]' => 'engage',
            'registration[user][nationality]' => 'FR',
            'registration[user][birthday]' => SeasonFactory::faker()->dateTimeBetween('-17 years', '-11 years')->format('Y-m-d'),
            'registration[user][laneNumber]' => '100',
            'registration[user][laneType]' => 'Rue',
            'registration[user][laneName]' => 'du test',
            'registration[user][city]' => 'One City',
            'registration[user][clubEmailAllowed]' => 1,
            'registration[agreeSwim]' => 1,
            'registration[agreeRulesAndRegulations]' => 1,
            'registration[user][firstLegalGuardian][role]' => '',
            'registration[user][firstLegalGuardian][firstName]' => '',
            'registration[user][firstLegalGuardian][lastName]' => '',
            'registration[user][firstLegalGuardian][email]' => '',
            'registration[user][firstLegalGuardian][phoneNumber]' => '',
            'registration[user][secondLegalGuardian][role]' => '',
            'registration[user][secondLegalGuardian][firstName]' => '',
            'registration[user][secondLegalGuardian][lastName]' => '',
            'registration[user][secondLegalGuardian][email]' => '',
            'registration[user][secondLegalGuardian][phoneNumber]' => '',
            'registration[license][medicalCertificate][type]' => MedicalCertificateType::Certificate->value,
            'registration[license][medicalCertificate][level]' => MedicalCertificateLevel::Competition->value,
            'registration[license][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration[license][optionalInsurance]' => 1,
            'registration[license][federationEmailAllowed]' => 1,
        ]);
        $form['registration[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Le membre est mineur, merci de renseigner un représentant légal.', $crawler->filter('form > div.alert.alert-danger')->text());
        $this->assertCount(1, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(0, $crawler->filter('.invalid-feedback'));
        UserFactory::repository()->assert()->count(0);
        LicenseFactory::repository()->assert()->count(0);
        MedicalCertificateFactory::repository()->assert()->count(0);
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
            'registration[user][postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration[user][gender]' => Gender::Male->value,
            'registration[user][firstName]' => 'John',
            'registration[user][lastName]' => 'Doe',
            'registration[user][email]' => 'john.doe@avirontours.fr',
            'registration[user][phoneNumber]' => '0102030405',
            'registration[user][plainPassword][first]' => 'engage',
            'registration[user][plainPassword][second]' => 'engage',
            'registration[user][nationality]' => 'FR',
            'registration[user][birthday]' => SeasonFactory::faker()->dateTimeBetween('-80 years', '-20 years')->format('Y-m-d'),
            'registration[user][laneNumber]' => '100',
            'registration[user][laneType]' => 'Rue',
            'registration[user][laneName]' => 'du test',
            'registration[user][city]' => 'One City',
            'registration[user][clubEmailAllowed]' => 1,
            'registration[agreeSwim]' => 1,
            'registration[agreeRulesAndRegulations]' => 1,
            'registration[user][firstLegalGuardian][role]' => '',
            'registration[user][firstLegalGuardian][firstName]' => '',
            'registration[user][firstLegalGuardian][lastName]' => '',
            'registration[user][firstLegalGuardian][email]' => '',
            'registration[user][firstLegalGuardian][phoneNumber]' => '',
            'registration[user][secondLegalGuardian][role]' => '',
            'registration[user][secondLegalGuardian][firstName]' => '',
            'registration[user][secondLegalGuardian][lastName]' => '',
            'registration[user][secondLegalGuardian][email]' => '',
            'registration[user][secondLegalGuardian][phoneNumber]' => '',
            'registration[license][medicalCertificate][type]' => MedicalCertificateType::Certificate->value,
            'registration[license][medicalCertificate][level]' => MedicalCertificateLevel::Competition->value,
            'registration[license][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration[license][optionalInsurance]' => 1,
            'registration[license][federationEmailAllowed]' => 1,
        ]);
        $form['registration[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $client->submit($form);

        $this->assertResponseRedirects();
        UserFactory::repository()->assert()->count(1);
        LicenseFactory::repository()->assert()->count(1);
        MedicalCertificateFactory::repository()->assert()->count(1);
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
            'registration[user][postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration[user][gender]' => Gender::Male->value,
            'registration[user][firstName]' => $license->getUser()->getFirstName(),
            'registration[user][lastName]' => $license->getUser()->getLastName(),
            'registration[user][email]' => 'john.doe@avirontours.fr',
            'registration[user][phoneNumber]' => '0102030405',
            'registration[user][plainPassword][first]' => 'engage',
            'registration[user][plainPassword][second]' => 'engage',
            'registration[user][nationality]' => 'FR',
            'registration[user][birthday]' => '2010-01-01',
            'registration[user][laneNumber]' => '100',
            'registration[user][laneType]' => 'Rue',
            'registration[user][laneName]' => 'du test',
            'registration[user][city]' => 'One City',
            'registration[user][clubEmailAllowed]' => 1,
            'registration[agreeSwim]' => 1,
            'registration[agreeRulesAndRegulations]' => 1,
            'registration[user][firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'registration[user][firstLegalGuardian][firstName]' => 'Gandalf',
            'registration[user][firstLegalGuardian][lastName]' => 'Le Blanc',
            'registration[user][firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'registration[user][firstLegalGuardian][phoneNumber]' => '0123456788',
            'registration[user][secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'registration[user][secondLegalGuardian][firstName]' => 'Galadriel',
            'registration[user][secondLegalGuardian][lastName]' => 'Artanis',
            'registration[user][secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'registration[user][secondLegalGuardian][phoneNumber]' => '0123456799',
            'registration[license][medicalCertificate][type]' => MedicalCertificateType::Certificate->value,
            'registration[license][medicalCertificate][level]' => MedicalCertificateLevel::Competition->value,
            'registration[license][medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration[license][optionalInsurance]' => 1,
            'registration[license][federationEmailAllowed]' => 1,
        ]);
        $form['registration[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Un compte existe déjà avec ce nom et prénom.', $crawler->filter('#registration_user_firstName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        UserFactory::repository()->assert()->count(1);
        LicenseFactory::repository()->assert()->count(1);
        MedicalCertificateFactory::repository()->assert()->count(1);
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
        PostalCodeFactory::createOne([
            'postalCode' => '01000',
            'city' => 'One City',
        ]);
        $user = UserFactory::createOne();
        LicenseFactory::createOne([
            'user' => $user,
        ]);
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user->object());
        $crawler = $client->request('GET', '/renew/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();
        $this->assertNotNull($crawler->filter('#renew_user_gender_0')->attr('disabled'));
        $this->assertNotNull($crawler->filter('#renew_user_gender_1')->attr('disabled'));
        $this->assertNotNull($crawler->filter('#renew_user_firstName')->attr('disabled'));
        $this->assertNotNull($crawler->filter('#renew_user_lastName')->attr('disabled'));
        $this->assertNotNull($crawler->filter('#renew_user_nationality')->attr('disabled'));
        $this->assertNotNull($crawler->filter('#renew_user_birthday')->attr('disabled'));

        // Simulate AJAX call
        $crawler = $client->submitForm('S\'inscrire', [
            'renew[user][postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'renew[user][email]' => 'john.doe@avirontours.fr',
            'renew[user][phoneNumber]' => '0102030405',
            'renew[user][laneNumber]' => '100',
            'renew[user][laneType]' => 'Rue',
            'renew[user][laneName]' => 'du test',
            'renew[user][city]' => 'One City',
            'renew[user][clubEmailAllowed]' => 1,
            'renew[agreeSwim]' => 1,
            'renew[agreeRulesAndRegulations]' => 1,
            'renew[user][firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'renew[user][firstLegalGuardian][firstName]' => 'Gandalf',
            'renew[user][firstLegalGuardian][lastName]' => 'Le Blanc',
            'renew[user][firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'renew[user][firstLegalGuardian][phoneNumber]' => '0123456788',
            'renew[user][secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'renew[user][secondLegalGuardian][firstName]' => 'Galadriel',
            'renew[user][secondLegalGuardian][lastName]' => 'Artanis',
            'renew[user][secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'renew[user][secondLegalGuardian][phoneNumber]' => '0123456799',
            'renew[license][medicalCertificate][type]' => MedicalCertificateType::Attestation->value,
            'renew[license][medicalCertificate][level]' => MedicalCertificateLevel::Competition->value,
            'renew[license][medicalCertificate][date]' => $date = (new \DateTime())->format('Y-m-d'),
            'renew[license][optionalInsurance]' => 1,
            'renew[license][federationEmailAllowed]' => 1,
        ]);
        $form['renew[license][medicalCertificate][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/document.pdf');
        $client->submit($form);

        $this->assertResponseRedirects();
        $this->assertQueuedEmailCount(1);
        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame((new \DateTime())->format('Y-m-d'), $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame('0102030405', $user->getPhoneNumber());
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
        $this->assertCount(2, $user->refresh()->getLicenses());
        $this->assertSame($season->getSeasonCategories()->first(), $user->getLicenses()->last()->getSeasonCategory());
        $this->assertTrue($user->getLicenses()->last()->getFederationEmailAllowed());
        $this->assertTrue($user->getLicenses()->last()->getOptionalInsurance());
        $this->assertNotNull($user->getLicenses()->last()->getMedicalCertificate());
        $this->assertSame(MedicalCertificateType::Attestation, $user->getLicenses()->last()->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificateLevel::Competition, $user->getLicenses()->last()->getMedicalCertificate()->getLevel());
        $this->assertSame($date, $user->getLicenses()->last()->getMedicalCertificate()->getdate()->format('Y-m-d'));
        $this->assertNotNull($user->getLicenses()->last()->getMedicalCertificate()->getUploadedFile());
        UserFactory::repository()->assert()->count(1);
        LicenseFactory::repository()->assert()->count(2);
        MedicalCertificateFactory::repository()->assert()->count(2);
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
