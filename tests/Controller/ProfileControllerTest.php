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

use App\Tests\AppWebTestCase;

class ProfileControllerTest extends AppWebTestCase
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

    public function urlProvider()
    {
        yield ['GET', '/profile'];
        yield ['GET', '/profile/edit'];
        yield ['GET', '/profile/edit-password'];
    }

    public function testProfileShow(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
    }

    public function testProfileEditProfile(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $user = $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/profile/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'profile[email]' => 'john.doe@avirontours.fr',
            'profile[phoneNumber]' => '0123456789',
            'profile[firstName]' => 'John',
            'profile[lastName]' => 'Doe',
            'profile[address][laneNumber]' => '2',
            'profile[address][laneType]' => 'Rue',
            'profile[address][laneName]' => 'du test',
            'profile[address][postalCode]' => '01000',
            'profile[address][city]' => 'One City',
            'profile[clubEmailAllowed]' => 1,
            'profile[partnersEmailAllowed]' => 1,
        ]);

        $this->assertResponseRedirects();
        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame('0123456789', $user->getPhoneNumber());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('2', $user->getLaneNumber());
        $this->assertSame('Rue', $user->getLaneType());
        $this->assertSame('Du Test', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertTrue($user->getClubEmailAllowed());
        $this->assertTrue($user->getPartnersEmailAllowed());
    }

    public function testProfileEditProfileWithoutData(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'a.user');
        $client->request('GET', '/profile/edit');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Modifier', [
            'profile[email]' => '',
            'profile[phoneNumber]' => '',
            'profile[firstName]' => '',
            'profile[lastName]' => '',
            'profile[address][laneNumber]' => '',
            'profile[address][laneType]' => '',
            'profile[address][laneName]' => '',
            'profile[address][postalCode]' => '',
            'profile[address][city]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_email')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_firstName')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_lastName')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_address_laneNumber')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#profile_address_laneType')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_address_laneName')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_address_postalCode')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_address_city')->parents()->filter('.invalid-feedback')->text());
        $this->assertCount(8, $crawler->filter('.invalid-feedback'));
    }

    public function testEditPassword(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $user = $this->logIn($client, 'ROLE_USER');
        $oldPassword = $user->getPassword();
        $client->request('GET', '/profile/edit-password');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'change_password[currentPassword]' => 'engage',
            'change_password[plainPassword][first]' => 'engage2',
            'change_password[plainPassword][second]' => 'engage2',
        ]);

        $this->assertResponseRedirects();
        $this->assertNotSame($oldPassword, $user->getPassword());
    }

    public function testEditPasswordWithoutData(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/profile/edit-password');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Modifier', [
            'change_password[currentPassword]' => '',
            'change_password[plainPassword][first]' => '',
            'change_password[plainPassword][second]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur doit être le mot de passe actuel de l\'utilisateur.', $crawler->filter('#change_password_currentPassword')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#change_password_plainPassword_first')->parents()->filter('.invalid-feedback')->text());
        $this->assertCount(2, $crawler->filter('.invalid-feedback'));
    }
}
