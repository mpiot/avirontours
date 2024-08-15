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

use App\Enum\LegalGuardianRole;
use App\Factory\PostalCodeFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

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

    public function urlProvider(): \Generator
    {
        yield ['GET', '/profile'];
        yield ['GET', '/profile/edit'];
        yield ['GET', '/profile/edit-password'];
    }

    public function testShowProfile(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/profile');

        $this->assertResponseIsSuccessful();
    }

    public function testEditProfile(): void
    {
        PostalCodeFactory::createOne([
            'postalCode' => '01000',
            'city' => 'One City',
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $user = $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/profile/edit');

        $this->assertResponseIsSuccessful();

        // Simulate AJAX call
        $crawler = $client->submitForm('Modifier', [
            'profile[postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('Modifier')->form([
            'profile[email]' => 'john.doe@avirontours.fr',
            'profile[phoneNumber]' => '0123456777',
            'profile[laneNumber]' => '2',
            'profile[laneType]' => 'Rue',
            'profile[laneName]' => 'du test',
            'profile[city]' => 'One City',
            'profile[firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'profile[firstLegalGuardian][firstName]' => 'Gandalf',
            'profile[firstLegalGuardian][lastName]' => 'Le Blanc',
            'profile[firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'profile[firstLegalGuardian][phoneNumber]' => '0123456788',
            'profile[secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'profile[secondLegalGuardian][firstName]' => 'Galadriel',
            'profile[secondLegalGuardian][lastName]' => 'Artanis',
            'profile[secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'profile[secondLegalGuardian][phoneNumber]' => '0123456799',
            'profile[clubEmailAllowed]' => 1,
        ]);
        $client->submit($form);

        $this->assertResponseRedirects();
        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame('0123456777', $user->getPhoneNumber());
        $this->assertSame('2', $user->getLaneNumber());
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
    }

    public function testEditProfileWithoutData(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'on-water.user');
        $client->request('GET', '/profile/edit');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Modifier', [
            'profile[email]' => '',
            'profile[phoneNumber]' => '',
            'profile[laneNumber]' => '',
            'profile[laneType]' => '',
            'profile[laneName]' => '',
            'profile[postalCode]' => '',
            'profile[city]' => '',
            'profile[firstLegalGuardian][role]' => '',
            'profile[firstLegalGuardian][firstName]' => '',
            'profile[firstLegalGuardian][lastName]' => '',
            'profile[firstLegalGuardian][email]' => '',
            'profile[firstLegalGuardian][phoneNumber]' => '',
            'profile[secondLegalGuardian][role]' => '',
            'profile[secondLegalGuardian][firstName]' => '',
            'profile[secondLegalGuardian][lastName]' => '',
            'profile[secondLegalGuardian][email]' => '',
            'profile[secondLegalGuardian][phoneNumber]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_email')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_laneNumber')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#profile_laneType')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_laneName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_postalCode')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#profile_city')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(6, $crawler->filter('.invalid-feedback'));
    }

    public function testEditProfileWithoutLegalGuardianForUnderEighteen(): void
    {
        $user = UserFactory::new()->minor()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'on-water.user');
        $client->loginUser($user->_real());
        $client->request('GET', '/profile/edit');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Modifier', [
            'profile[firstLegalGuardian][role]' => '',
            'profile[firstLegalGuardian][firstName]' => '',
            'profile[firstLegalGuardian][lastName]' => '',
            'profile[firstLegalGuardian][email]' => '',
            'profile[firstLegalGuardian][phoneNumber]' => '',
            'profile[secondLegalGuardian][role]' => '',
            'profile[secondLegalGuardian][firstName]' => '',
            'profile[secondLegalGuardian][lastName]' => '',
            'profile[secondLegalGuardian][email]' => '',
            'profile[secondLegalGuardian][phoneNumber]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Le membre est mineur, merci de renseigner un représentant légal.', $crawler->filter('form > div.alert.alert-danger')->text());
        $this->assertCount(1, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(0, $crawler->filter('.invalid-feedback'));
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

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur doit être le mot de passe actuel de l\'utilisateur.', $crawler->filter('#change_password_currentPassword')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#change_password_plainPassword_first')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(2, $crawler->filter('.invalid-feedback'));
    }
}
