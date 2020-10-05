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
            'profile[phoneNumber]' => '',
            'profile[firstName]' => '',
            'profile[lastName]' => '',
            'profile[postalCode]' => '',
            'profile[city]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_email"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_firstName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_lastName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_postalCode"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="profile_city"] .form-error-message')->text());
        $this->assertCount(5, $crawler->filter('.form-error-message'));

        $form = $crawler->selectButton('Modifier')->form([
            'profile[email]' => 'john.doe@avirontours.fr',
            'profile[phoneNumber]' => '0123456789',
            'profile[firstName]' => 'John',
            'profile[lastName]' => 'Doe',
            'profile[postalCode]' => '01000',
            'profile[city]' => 'One City',
            'profile[clubEmailAllowed]' => 1,
            'profile[partnersEmailAllowed]' => 1,
        ]);
        $client->submit($form);
        $this->assertResponseRedirects();
        /** @var User $user */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'john.doe@avirontours.fr']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame('0123456789', $user->getPhoneNumber());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertTrue($user->getClubEmailAllowed());
        $this->assertTrue($user->getPartnersEmailAllowed());
    }

    public function testEditPassword()
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
