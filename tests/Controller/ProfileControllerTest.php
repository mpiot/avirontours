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

use App\Entity\User;
use App\Tests\AppWebTestCase;

class ProfileControllerTest extends AppWebTestCase
{
    public function testProfileShow()
    {
        $client = static::createClient();
        $url = '/profile/';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public function testProfileEditProfile()
    {
        $client = static::createClient();
        $url = '/profile/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Modifier', [
            'profile[email]' => '',
            'profile[firstName]' => '',
            'profile[lastName]' => '',
            'profile[legalRepresentative]' => '',
            'profile[address][laneNumber]' => '',
            'profile[address][laneType]' => '',
            'profile[address][laneName]' => '',
            'profile[address][postalCode]' => '',
            'profile[address][city]' => '',
            'profile[address][phoneNumber]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_email"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_firstName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_lastName"] .form-error-message')->text());
        $this->assertStringContainsString('Le membre est mineur, merci de renseigner un représentant légal.', $crawler->filter('label[for="profile_legalRepresentative"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_address_laneNumber"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_address_laneName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('label[for="profile_address_laneType"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_address_postalCode"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_address_city"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_address_phoneNumber"] .form-error-message')->text());
        $this->assertCount(10, $crawler->filter('.form-error-message'));

        $form = $crawler->selectButton('Modifier')->form([
            'profile[email]' => 'john.doe@avirontours.fr',
            'profile[firstName]' => 'John',
            'profile[lastName]' => 'Doe',
            'profile[legalRepresentative]' => 'Miss Doe',
            'profile[address][laneNumber]' => '999',
            'profile[address][laneType]' => 'Rue',
            'profile[address][laneName]' => 'de Ouf',
            'profile[address][postalCode]' => '01000',
            'profile[address][city]' => 'One City',
            'profile[address][phoneNumber]' => '0123456789',
        ]);
        $client->submit($form);
        $this->assertResponseRedirects();
        /** @var User $user */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'john.doe@avirontours.fr']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('Miss Doe', $user->getLegalRepresentative());
        $this->assertSame('999', $user->getLaneNumber());
        $this->assertSame('Rue', $user->getLaneType());
        $this->assertSame('de Ouf', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertSame('0123456789', $user->getPhoneNumber());
    }

    public function testRegistration()
    {
        $client = static::createClient();
        $url = '/profile/edit-password';

        $originalPassword = $this->getEntityManager()->getRepository(User::class)->findOneBy(['username' => 'a.user'])->getPassword();

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Modifier', [
            'change_password[currentPassword]' => '',
            'change_password[plainPassword][first]' => '',
            'change_password[plainPassword][second]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur doit être le mot de passe actuel de l\'utilisateur.', $crawler->filter('label[for="change_password_currentPassword"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="change_password_plainPassword_first"] .form-error-message')->text());
        $this->assertCount(2, $crawler->filter('.form-error-message'));

        $form = $crawler->selectButton('Modifier')->form([
            'change_password[currentPassword]' => 'engage',
            'change_password[plainPassword][first]' => 'engage2',
            'change_password[plainPassword][second]' => 'engage2',
        ]);
        $client->submit($form);
        $this->assertResponseRedirects();
        /** @var User $user */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['username' => 'a.user']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertNotSame($originalPassword, $user->getPassword());
    }
}
