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
use App\Tests\AppWebTestCase;

class RegistrationControllerTest extends AppWebTestCase
{
    public function testRegistration()
    {
        $client = static::createClient();
        $url = '/register/2020-jeune';

        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'registration_form[firstName]' => '',
            'registration_form[lastName]' => '',
            'registration_form[email]' => '',
            'registration_form[plainPassword][first]' => '',
            'registration_form[plainPassword][second]' => '',
            'registration_form[birthday]' => '',
            'registration_form[legalRepresentative]' => '',
            'registration_form[address][laneNumber]' => '',
            'registration_form[address][laneName]' => '',
            'registration_form[address][postalCode]' => '',
            'registration_form[address][city]' => '',
            'registration_form[address][phoneNumber]' => '',
            'registration_form[medicalCertificate][date]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_gender')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_firstName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_lastName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_email"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_plainPassword_first"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_birthday"] .form-error-message')->text());
        $this->assertStringContainsString('Le membre est mineur, merci de renseigner un représentant légal.', $crawler->filter('label[for="registration_form_legalRepresentative"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_address_laneNumber"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_address_laneName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_address_postalCode"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_address_city"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_address_phoneNumber"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_medicalCertificate_level')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_medicalCertificate_date"] .form-error-message')->text());
        $this->assertStringContainsString('Vous devez accepter les conditions d\'utilisation.', $crawler->filter('label[for="registration_form_agreeTerms"] .form-error-message')->text());
        $this->assertCount(15, $crawler->filter('.form-error-message'));

        $form = $crawler->selectButton('Sauver')->form([
            'registration_form[gender]' => 'm',
            'registration_form[firstName]' => 'John',
            'registration_form[lastName]' => 'Doe',
            'registration_form[email]' => 'john.doe@avirontours.fr',
            'registration_form[plainPassword][first]' => 'engage',
            'registration_form[plainPassword][second]' => 'engage',
            'registration_form[birthday]' => '2010-01-01',
            'registration_form[legalRepresentative]' => 'Miss Doe',
            'registration_form[address][laneNumber]' => '999',
            'registration_form[address][laneType]' => 'Rue',
            'registration_form[address][laneName]' => 'de ouf',
            'registration_form[address][postalCode]' => '01000',
            'registration_form[address][city]' => 'One City',
            'registration_form[address][phoneNumber]' => '0102030405',
            'registration_form[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration_form[medicalCertificate][date]' => '2020-01-01',
            'registration_form[agreeTerms]' => 1,
        ]);
        $form['registration_form[medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
        $client->submit($form);
        $this->assertResponseRedirects();
        /** @var User $user */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'john.doe@avirontours.fr']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame((new \DateTime())->format('Y-m-d'), $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame('m', $user->getGender());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertNotNull($user->getPassword());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('Miss Doe', $user->getLegalRepresentative());
        $this->assertSame('999', $user->getLaneNumber());
        $this->assertSame('Rue', $user->getLaneType());
        $this->assertSame('de ouf', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertSame('0102030405', $user->getPhoneNumber());
        $this->assertSame(User::ROWER_CATEGORY_C, $user->getRowerCategory());
        $this->assertCount(1, $user->getLicenses());
        $this->assertNotNull($user->getLicenses()->first()->getSeasonCategory());
        $this->assertNotNull($user->getLicenses()->first()->getMedicalCertificate());
        $this->assertSame(MedicalCertificate::TYPE_CERTIFICATE, $user->getLicenses()->first()->getMedicalCertificate()->getType());
    }

    public function testNonEnabledRegistration()
    {
        $client = static::createClient();
        $url = '/register/2018-jeune';

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testRegistrationAsLogInUser()
    {
        $client = static::createClient();
        $url = '/register/2020-indoor';

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseRedirects('/profile/');
    }

    public function testRenew()
    {
        $client = static::createClient();
        $url = '/renew/2020-jeune';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('S\'inscrire')->form([
            'renew[medicalCertificate][type]' => MedicalCertificate::TYPE_ATTESTATION,
            'renew[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'renew[medicalCertificate][date]' => '2020-01-01',
        ]);
        $form['renew[medicalCertificate][file][file]']->upload(__DIR__.'/../../src/DataFixtures/Files/medical-certificate.pdf');
        $client->submit($form);
        $this->assertResponseRedirects();
        /** @var User $user */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['username' => 'a.user']);
        $this->assertCount(3, $user->getLicenses());
        $this->assertSame(MedicalCertificate::TYPE_ATTESTATION, $user->getLicenses()->last()->getMedicalCertificate()->getType());
        $this->assertSame(MedicalCertificate::LEVEL_COMPETITION, $user->getLicenses()->last()->getMedicalCertificate()->getLevel());
        $this->assertSame('2020-01-01', $user->getLicenses()->last()->getMedicalCertificate()->getdate()->format('Y-m-d'));

        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testNonEnabledRenew()
    {
        $client = static::createClient();
        $url = '/renew/2018-jeune';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testRenewAsAnonymousUser()
    {
        $client = static::createClient();
        $url = '/renew/2020-indoor';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');
    }
}
