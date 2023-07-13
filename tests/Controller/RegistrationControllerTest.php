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
        $crawler = $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();

        // Simulate AJAX call
        $crawler = $client->submitForm('S\'inscrire', [
            'registration_form[postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration_form[gender]' => 'm',
            'registration_form[firstName]' => 'John',
            'registration_form[lastName]' => 'Doe',
            'registration_form[email]' => 'john.doe@avirontours.fr',
            'registration_form[phoneNumber]' => '0102030405',
            'registration_form[plainPassword][first]' => 'engage',
            'registration_form[plainPassword][second]' => 'engage',
            'registration_form[birthCountry]' => 'FR',
            'registration_form[birthday]' => '2010-01-01',
            'registration_form[laneNumber]' => '100',
            'registration_form[laneType]' => 'Rue',
            'registration_form[laneName]' => 'du test',
            'registration_form[city]' => 'One City',
            'registration_form[firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'registration_form[firstLegalGuardian][firstName]' => 'Gandalf',
            'registration_form[firstLegalGuardian][lastName]' => 'Le Blanc',
            'registration_form[firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'registration_form[firstLegalGuardian][phoneNumber]' => '0123456788',
            'registration_form[secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'registration_form[secondLegalGuardian][firstName]' => 'Galadriel',
            'registration_form[secondLegalGuardian][lastName]' => 'Artanis',
            'registration_form[secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'registration_form[secondLegalGuardian][phoneNumber]' => '0123456799',
            'registration_form[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration_form[medicalCertificate][date]' => $date = (new \DateTime())->format('Y-m-d'),
            'registration_form[clubEmailAllowed]' => 1,
            'registration_form[federationEmailAllowed]' => 1,
            'registration_form[optionalInsurance]' => 1,
            'registration_form[agreeSwim]' => 1,
        ]);
        $form['registration_form[medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
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
        $this->assertSame('FR', $user->getBirthCountry());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('100', $user->getLaneNumber());
        $this->assertSame('Rue', $user->getLaneType());
        $this->assertSame('Du Test', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
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
        $this->assertTrue($user->getClubEmailAllowed());
        $this->assertCount(1, $user->getLicenses());
        $this->assertNotNull($user->getLicenses()->first()->getSeasonCategory());
        $this->assertNotNull($user->getLicenses()->first()->getMedicalCertificate());
        $this->assertSame(MedicalCertificate::TYPE_CERTIFICATE, $user->getLicenses()->first()->getMedicalCertificate()->getType());
        $this->assertTrue($user->getLicenses()->first()->getFederationEmailAllowed());
        $this->assertTrue($user->getLicenses()->first()->getOptionalInsurance());
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
            'registration_form[firstName]' => '',
            'registration_form[lastName]' => '',
            'registration_form[email]' => '',
            'registration_form[phoneNumber]' => '',
            'registration_form[plainPassword][first]' => '',
            'registration_form[plainPassword][second]' => '',
            'registration_form[birthCountry]' => '',
            'registration_form[birthday]' => '',
            'registration_form[laneNumber]' => '',
            'registration_form[laneType]' => '',
            'registration_form[laneName]' => '',
            'registration_form[postalCode]' => '',
            'registration_form[city]' => '',
            'registration_form[firstLegalGuardian][role]' => '',
            'registration_form[firstLegalGuardian][firstName]' => '',
            'registration_form[firstLegalGuardian][lastName]' => '',
            'registration_form[firstLegalGuardian][email]' => '',
            'registration_form[firstLegalGuardian][phoneNumber]' => '',
            'registration_form[secondLegalGuardian][role]' => '',
            'registration_form[secondLegalGuardian][firstName]' => '',
            'registration_form[secondLegalGuardian][lastName]' => '',
            'registration_form[secondLegalGuardian][email]' => '',
            'registration_form[secondLegalGuardian][phoneNumber]' => '',
            'registration_form[medicalCertificate][date]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_gender')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_firstName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_lastName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_email')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_plainPassword_first')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_birthCountry')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_birthday')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_laneNumber')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#registration_form_laneType')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_laneName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_postalCode')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_city')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_medicalCertificate_level')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_medicalCertificate_date')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('input#registration_form_medicalCertificate_file_file')->closest('fieldset')->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Vous devez savoir nager 25m avec un départ plongé pour vous inscrire.', $crawler->filter('#registration_form_agreeSwim')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(16, $crawler->filter('.invalid-feedback'));
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
        $crawler = $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();

        // Simulate AJAX call
        $crawler = $client->submitForm('S\'inscrire', [
            'registration_form[postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration_form[gender]' => 'm',
            'registration_form[firstName]' => 'John',
            'registration_form[lastName]' => 'Doe',
            'registration_form[email]' => 'john.doe@avirontours.fr',
            'registration_form[phoneNumber]' => '0123456789',
            'registration_form[plainPassword][first]' => 'engage',
            'registration_form[plainPassword][second]' => 'engage',
            'registration_form[birthCountry]' => 'FR',
            'registration_form[birthday]' => SeasonFactory::faker()->dateTimeBetween('-17 years', '-11 years')->format('Y-m-d'),
            'registration_form[laneNumber]' => '100',
            'registration_form[laneType]' => 'Rue',
            'registration_form[laneName]' => 'du test',
            'registration_form[city]' => 'One City',
            'registration_form[firstLegalGuardian][role]' => '',
            'registration_form[firstLegalGuardian][firstName]' => '',
            'registration_form[firstLegalGuardian][lastName]' => '',
            'registration_form[firstLegalGuardian][email]' => '',
            'registration_form[firstLegalGuardian][phoneNumber]' => '',
            'registration_form[secondLegalGuardian][role]' => '',
            'registration_form[secondLegalGuardian][firstName]' => '',
            'registration_form[secondLegalGuardian][lastName]' => '',
            'registration_form[secondLegalGuardian][email]' => '',
            'registration_form[secondLegalGuardian][phoneNumber]' => '',
            'registration_form[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration_form[medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration_form[clubEmailAllowed]' => 1,
            'registration_form[federationEmailAllowed]' => 1,
            'registration_form[optionalInsurance]' => 1,
            'registration_form[agreeSwim]' => 1,
        ]);
        $form['registration_form[medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
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
        $crawler = $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();

        // Simulate AJAX call
        $crawler = $client->submitForm('S\'inscrire', [
            'registration_form[postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration_form[gender]' => 'm',
            'registration_form[firstName]' => 'John',
            'registration_form[lastName]' => 'Doe',
            'registration_form[email]' => 'john.doe@avirontours.fr',
            'registration_form[phoneNumber]' => '0123456789',
            'registration_form[plainPassword][first]' => 'engage',
            'registration_form[plainPassword][second]' => 'engage',
            'registration_form[birthCountry]' => 'FR',
            'registration_form[birthday]' => SeasonFactory::faker()->dateTimeBetween('-80 years', '-20 years')->format('Y-m-d'),
            'registration_form[laneNumber]' => '100',
            'registration_form[laneType]' => 'Rue',
            'registration_form[laneName]' => 'du test',
            'registration_form[city]' => 'One City',
            'registration_form[firstLegalGuardian][role]' => '',
            'registration_form[firstLegalGuardian][firstName]' => '',
            'registration_form[firstLegalGuardian][lastName]' => '',
            'registration_form[firstLegalGuardian][email]' => '',
            'registration_form[firstLegalGuardian][phoneNumber]' => '',
            'registration_form[secondLegalGuardian][role]' => '',
            'registration_form[secondLegalGuardian][firstName]' => '',
            'registration_form[secondLegalGuardian][lastName]' => '',
            'registration_form[secondLegalGuardian][email]' => '',
            'registration_form[secondLegalGuardian][phoneNumber]' => '',
            'registration_form[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration_form[medicalCertificate][date]' => $date = (new \DateTime())->format('Y-m-d'),
            'registration_form[clubEmailAllowed]' => 1,
            'registration_form[federationEmailAllowed]' => 1,
            'registration_form[optionalInsurance]' => 1,
            'registration_form[agreeSwim]' => 1,
        ]);
        $form['registration_form[medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
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
        $crawler = $client->request('GET', '/register/'.$license->getSeasonCategory()->getSlug());

        $this->assertResponseIsSuccessful();

        // Simulate AJAX call
        $crawler = $client->submitForm('S\'inscrire', [
            'registration_form[postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('S\'inscrire')->form([
            'registration_form[gender]' => 'm',
            'registration_form[firstName]' => $license->getUser()->getFirstName(),
            'registration_form[lastName]' => $license->getUser()->getLastName(),
            'registration_form[email]' => 'annual-a@avirontours.fr',
            'registration_form[phoneNumber]' => '0102030405',
            'registration_form[plainPassword][first]' => 'engage',
            'registration_form[plainPassword][second]' => 'engage',
            'registration_form[birthCountry]' => 'FR',
            'registration_form[birthday]' => '2010-01-01',
            'registration_form[laneNumber]' => '100',
            'registration_form[laneType]' => 'Rue',
            'registration_form[laneName]' => 'du test',
            'registration_form[city]' => 'One City',
            'registration_form[firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'registration_form[firstLegalGuardian][firstName]' => 'Gandalf',
            'registration_form[firstLegalGuardian][lastName]' => 'Le Blanc',
            'registration_form[firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'registration_form[firstLegalGuardian][phoneNumber]' => '0123456788',
            'registration_form[secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'registration_form[secondLegalGuardian][firstName]' => 'Galadriel',
            'registration_form[secondLegalGuardian][lastName]' => 'Artanis',
            'registration_form[secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'registration_form[secondLegalGuardian][phoneNumber]' => '0123456799',
            'registration_form[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration_form[medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration_form[clubEmailAllowed]' => 1,
            'registration_form[federationEmailAllowed]' => 1,
            'registration_form[optionalInsurance]' => 1,
            'registration_form[agreeSwim]' => 1,
        ]);
        $form['registration_form[medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Un compte existe déjà avec ce nom et prénom.', $crawler->filter('#registration_form_firstName')->ancestors()->filter('.invalid-feedback')->text());
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
