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

namespace App\Tests\Functional;

use App\Entity\MedicalCertificate;
use App\Entity\User;
use App\Tests\AppWebTestCase;

class RegistrationControllerTest extends AppWebTestCase
{
    public function testAnnualRegistration()
    {
        $client = static::createClient();
        $url = '/register/annual';

        $client->request('GET', $url);
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
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_medicalCertificate_type')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_medicalCertificate_level')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_medicalCertificate_date"] .form-error-message')->text());
        $this->assertStringContainsString('Vous devez accepter les conditions d\'utilisation.', $crawler->filter('label[for="registration_form_agreeTerms"] .form-error-message')->text());
        $this->assertCount(16, $crawler->filter('.form-error-message'));

        $client->submitForm('Sauver', [
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
            'registration_form[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'registration_form[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration_form[medicalCertificate][date]' => '2020-01-01',
            'registration_form[agreeTerms]' => 1,
        ]);
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
        $this->assertSame(User::LICENSE_TYPE_ANNUAL, $user->getLicenseType());
        $this->assertNull($user->getLicenseEndAt());
        $this->assertNull($user->getLicenseNumber());
        $this->assertSame(User::ROWER_CATEGORY_C, $user->getRowerCategory());
        $this->assertCount(1, $user->getSeasonUsers());
        $this->assertNotNull($user->getSeasonUsers()->first()->getSeason());
        $this->assertNotNull($user->getSeasonUsers()->first()->getMedicalCertificate());
    }

    public function testIndoorRegistration()
    {
        $client = static::createClient();
        $url = '/register/indoor';

        $client->request('GET', $url);
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
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_medicalCertificate_type')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#registration_form_medicalCertificate_level')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="registration_form_medicalCertificate_date"] .form-error-message')->text());
        $this->assertStringContainsString('Vous devez accepter les conditions d\'utilisation.', $crawler->filter('label[for="registration_form_agreeTerms"] .form-error-message')->text());
        $this->assertCount(16, $crawler->filter('.form-error-message'));

        $client->submitForm('Sauver', [
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
            'registration_form[medicalCertificate][type]' => MedicalCertificate::TYPE_CERTIFICATE,
            'registration_form[medicalCertificate][level]' => MedicalCertificate::LEVEL_COMPETITION,
            'registration_form[medicalCertificate][date]' => '2020-01-01',
            'registration_form[agreeTerms]' => 1,
        ]);
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
        $this->assertSame(User::LICENSE_TYPE_INDOOR, $user->getLicenseType());
        $this->assertNull($user->getLicenseEndAt());
        $this->assertNull($user->getLicenseNumber());
        $this->assertSame(User::ROWER_CATEGORY_C, $user->getRowerCategory());
        $this->assertCount(1, $user->getSeasonUsers());
        $this->assertNotNull($user->getSeasonUsers()->first()->getSeason());
        $this->assertNotNull($user->getSeasonUsers()->first()->getMedicalCertificate());
    }
}
