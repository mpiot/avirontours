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

namespace App\Tests\Controller;

use App\Entity\MedicalCertificate;
use App\Entity\User;
use App\Factory\LicenseFactory;
use App\Factory\SeasonFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class RegistrationControllerTest extends AppWebTestCase
{
    public function testRegistration()
    {
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauver')->form([
            'registration_form[gender]' => 'm',
            'registration_form[firstName]' => 'John',
            'registration_form[lastName]' => 'Doe',
            'registration_form[email]' => 'john.doe@avirontours.fr',
            'registration_form[phoneNumber]' => '0102030405',
            'registration_form[plainPassword][first]' => 'engage',
            'registration_form[plainPassword][second]' => 'engage',
            'registration_form[birthday]' => '2010-01-01',
            'registration_form[address][laneNumber]' => '100',
            'registration_form[address][laneType]' => 'Rue',
            'registration_form[address][laneName]' => 'du test',
            'registration_form[address][postalCode]' => '01000',
            'registration_form[address][city]' => 'One City',
            'registration_form[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration_form[medicalCertificate][date]' => $date = (new \DateTime())->format('Y-m-d'),
            'registration_form[agreeSwim]' => 1,
            'registration_form[federationEmailAllowed]' => 1,
            'registration_form[clubEmailAllowed]' => 1,
            'registration_form[partnersEmailAllowed]' => 1,
        ]);
        $form['registration_form[medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
        $client->submit($form);

        $this->assertResponseRedirects();

        $user = UserFactory::repository()->findOneBy(['email' => 'john.doe@avirontours.fr']);

        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame((new \DateTime())->format('Y-m-d'), $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame('m', $user->getGender());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('0102030405', $user->getPhoneNumber());
        $this->assertNotNull($user->getPassword());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('100', $user->getLaneNumber());
        $this->assertSame('Rue', $user->getLaneType());
        $this->assertSame('Du Test', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertTrue($user->getClubEmailAllowed());
        $this->assertTrue($user->getPartnersEmailAllowed());
        $this->assertSame(User::ROWER_CATEGORY_C, $user->getRowerCategory());
        $this->assertCount(1, $user->getLicenses());
        $this->assertNotNull($user->getLicenses()->first()->getSeasonCategory());
        $this->assertNotNull($user->getLicenses()->first()->getMedicalCertificate());
        $this->assertSame(MedicalCertificate::TYPE_CERTIFICATE, $user->getLicenses()->first()->getMedicalCertificate()->getType());
        $this->assertTrue($user->getLicenses()->first()->getFederationEmailAllowed());

        UserFactory::repository()->assertCount(1);
        LicenseFactory::repository()->assertCount(1);
    }

    public function testRegistrationWithoutData()
    {
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'registration_form[firstName]' => '',
            'registration_form[lastName]' => '',
            'registration_form[email]' => '',
            'registration_form[phoneNumber]' => '',
            'registration_form[plainPassword][first]' => '',
            'registration_form[plainPassword][second]' => '',
            'registration_form[birthday]' => '',
            'registration_form[address][laneNumber]' => '',
            'registration_form[address][laneType]' => '',
            'registration_form[address][laneName]' => '',
            'registration_form[address][postalCode]' => '',
            'registration_form[address][city]' => '',
            'registration_form[medicalCertificate][date]' => '',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_gender')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_firstName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_lastName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_email"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_plainPassword_first"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_birthday"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_address_laneNumber"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('label[for="registration_form_address_laneType"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_address_laneName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_address_postalCode"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_address_city"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_medicalCertificate_level')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_medicalCertificate_date"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('input#registration_form_medicalCertificate_file_file')->closest('fieldset')->filter('.form-error-message')->text());
        $this->assertStringContainsString('Vous devez savoir nager 25m avec un départ plongé pour vous inscrire.', $crawler->filter('label[for="registration_form_agreeSwim"] .form-error-message')->text());
        $this->assertCount(15, $crawler->filter('.form-error-message'));

        UserFactory::repository()->assertCount(0);
        LicenseFactory::repository()->assertCount(0);
    }

    public function testRegistrationTwice()
    {
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();
        $license = LicenseFactory::createOne(['seasonCategory' => $season->getSeasonCategories()->first()]);

        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $client->request('GET', '/register/'.$license->getSeasonCategory()->getSlug());

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauver')->form([
            'registration_form[gender]' => 'm',
            'registration_form[firstName]' => $license->getUser()->getFirstName(),
            'registration_form[lastName]' => $license->getUser()->getLastName(),
            'registration_form[email]' => 'annual-a@avirontours.fr',
            'registration_form[phoneNumber]' => '0102030405',
            'registration_form[plainPassword][first]' => 'engage',
            'registration_form[plainPassword][second]' => 'engage',
            'registration_form[birthday]' => '2010-01-01',
            'registration_form[address][laneNumber]' => '100',
            'registration_form[address][laneType]' => 'Rue',
            'registration_form[address][laneName]' => 'du test',
            'registration_form[address][postalCode]' => '01000',
            'registration_form[address][city]' => 'One City',
            'registration_form[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration_form[medicalCertificate][date]' => (new \DateTime())->format('Y-m-d'),
            'registration_form[agreeSwim]' => 1,
        ]);
        $form['registration_form[medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
        $crawler = $client->submit($form);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Un compte existe déjà avec ce nom et prénom.', $crawler->filter('label[for="registration_form_firstName"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        UserFactory::repository()->assertCount(1);
        LicenseFactory::repository()->assertCount(1);
    }

    public function testNonEnabledRegistration()
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testNonDisplayedCategoryRegistration()
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesNotDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testRegistrationAsLogInUser()
    {
        $season = SeasonFactory::new()->subscriptionEnabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/register/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseRedirects('/profile');
    }

    public function testRenew()
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
            'renew[agreeSwim]' => 1,
            'renew[federationEmailAllowed]' => 1,
        ]);

        $form['renew[medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
        $client->submit($form);

        $this->assertResponseRedirects();
        $this->assertCount(1, $user->getLicenses());
        $this->assertSame(MedicalCertificate::TYPE_ATTESTATION, $user->getLicenses()->last()->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificate::LEVEL_COMPETITION, $user->getLicenses()->last()->getMedicalCertificate()->getLevel());
        $this->assertSame($date, $user->getLicenses()->last()->getMedicalCertificate()->getdate()->format('Y-m-d'));
        $this->assertTrue($user->getLicenses()->last()->getFederationEmailAllowed());
    }

    public function testNonDisplayedCategoryRenew()
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesNotDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/renew/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testNonEnabledRenew()
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/renew/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testRenewAsAnonymousUser()
    {
        $season = SeasonFactory::new()->subscriptionDisabled()->seasonCategoriesDisplayed()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/renew/'.$season->getSeasonCategories()->first()->getSlug());

        $this->assertResponseRedirects('/login');
    }
}
