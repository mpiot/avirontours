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

namespace App\Tests\Controller\Admin;

use App\Enum\LegalGuardianRole;
use App\Factory\PostalCodeFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AppWebTestCase
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

    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForRegularUser($method, $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $user = UserFactory::createOne();
            $url = str_replace('{id}', (string) $user->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider(): \Generator
    {
        yield ['GET', '/admin/user'];
        yield ['GET', '/admin/user/{id}'];
        yield ['GET', '/admin/user/new'];
        yield ['POST', '/admin/user/new'];
        yield ['GET', '/admin/user/{id}/edit'];
        yield ['POST', '/admin/user/{id}/edit'];
        yield ['POST', '/admin/user/{id}'];
    }

    public function testIndexUsers(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/user');

        $this->assertResponseIsSuccessful();
    }

    public function testShowUser(): void
    {
        $user = UserFactory::createOne();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/user/'.$user->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testNewUser(): void
    {
        PostalCodeFactory::createOne([
            'postalCode' => '01000',
            'city' => 'One City',
        ]);

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/user/new');

        $this->assertResponseIsSuccessful();

        // Simulate AJAX call
        $crawler = $client->submitForm('Sauver', [
            'user[postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('Sauver')->form([
            'user[subscriptionDate]' => '2019-09-01',
            'user[gender]' => 'm',
            'user[firstName]' => 'John',
            'user[lastName]' => 'Doe',
            'user[email]' => 'john.doe@avirontours.fr',
            'user[phoneNumber]' => '0102030405',
            'user[nationality]' => 'FR',
            'user[birthday]' => '2010-01-01',
            'user[laneNumber]' => '100',
            'user[laneType]' => 'Avenue',
            'user[laneName]' => 'du test',
            'user[city]' => 'One City',
            'user[licenseNumber]' => '0123456789',
            'user[firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'user[firstLegalGuardian][firstName]' => 'Gandalf',
            'user[firstLegalGuardian][lastName]' => 'Le Blanc',
            'user[firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'user[firstLegalGuardian][phoneNumber]' => '0123456788',
            'user[secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'user[secondLegalGuardian][firstName]' => 'Galadriel',
            'user[secondLegalGuardian][lastName]' => 'Artanis',
            'user[secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'user[secondLegalGuardian][phoneNumber]' => '0123456799',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects();

        $user = UserFactory::repository()->findOneBy(['email' => 'john.doe@avirontours.fr']);

        $this->assertSame('2019-09-01', $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame('m', $user->getGender());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('0102030405', $user->getPhoneNumber());
        $this->assertNull($user->getPassword());
        $this->assertSame('FR', $user->getNationality());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('100', $user->getLaneNumber());
        $this->assertSame('Avenue', $user->getLaneType());
        $this->assertSame('Du Test', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertSame('0123456789', $user->getLicenseNumber());
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
    }

    public function testNewUserWithoutData(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/user/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'user[subscriptionDate]' => '',
            'user[firstName]' => '',
            'user[lastName]' => '',
            'user[email]' => '',
            'user[phoneNumber]' => '',
            'user[nationality]' => '',
            'user[birthday]' => '',
            'user[laneNumber]' => '',
            'user[laneType]' => '',
            'user[laneName]' => '',
            'user[postalCode]' => '',
            'user[city]' => '',
            'user[firstLegalGuardian][role]' => '',
            'user[firstLegalGuardian][firstName]' => '',
            'user[firstLegalGuardian][lastName]' => '',
            'user[firstLegalGuardian][email]' => '',
            'user[firstLegalGuardian][phoneNumber]' => '',
            'user[secondLegalGuardian][role]' => '',
            'user[secondLegalGuardian][firstName]' => '',
            'user[secondLegalGuardian][lastName]' => '',
            'user[secondLegalGuardian][email]' => '',
            'user[secondLegalGuardian][phoneNumber]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_subscriptionDate')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_gender')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_firstName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_lastName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_email')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_nationality')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_birthday')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_laneNumber')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#user_laneType')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_laneName')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_postalCode')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_city')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(12, $crawler->filter('.invalid-feedback'));
    }

    public function testEditUser(): void
    {
        PostalCodeFactory::createOne([
            'postalCode' => '01000',
            'city' => 'One City',
        ]);
        $user = UserFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/user/'.$user->getId().'/edit');
        $this->assertResponseIsSuccessful();

        // Simulate AJAX call
        $crawler = $client->submitForm('Modifier', [
            'user_edit[postalCode]' => '01000',
        ]);

        $form = $crawler->selectButton('Modifier')->form([
            'user_edit[subscriptionDate]' => '2019-09-01',
            'user_edit[gender]' => 'm',
            'user_edit[firstName]' => 'John',
            'user_edit[lastName]' => 'Doe',
            'user_edit[email]' => 'john.doe@avirontours.fr',
            'user_edit[phoneNumber]' => '0102030405',
            'user_edit[nationality]' => 'FR',
            'user_edit[birthday]' => '2010-01-01',
            'user_edit[laneNumber]' => '100',
            'user_edit[laneType]' => 'Avenue',
            'user_edit[laneName]' => 'du test',
            'user_edit[city]' => 'One City',
            'user_edit[licenseNumber]' => '0123456789',
            'user_edit[firstLegalGuardian][role]' => LegalGuardianRole::Father->value,
            'user_edit[firstLegalGuardian][firstName]' => 'Gandalf',
            'user_edit[firstLegalGuardian][lastName]' => 'Le Blanc',
            'user_edit[firstLegalGuardian][email]' => 'g.le-blanc@avirontours.fr',
            'user_edit[firstLegalGuardian][phoneNumber]' => '0123456788',
            'user_edit[secondLegalGuardian][role]' => LegalGuardianRole::Mother->value,
            'user_edit[secondLegalGuardian][firstName]' => 'Galadriel',
            'user_edit[secondLegalGuardian][lastName]' => 'Artanis',
            'user_edit[secondLegalGuardian][email]' => 'g.artanis@avirontours.fr',
            'user_edit[secondLegalGuardian][phoneNumber]' => '0123456799',
        ]);
        $client->submit($form);

        $this->assertResponseRedirects();
        $this->assertSame('2019-09-01', $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame('m', $user->getGender());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame('0102030405', $user->getPhoneNumber());
        $this->assertSame('FR', $user->getNationality());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('100', $user->getLaneNumber());
        $this->assertSame('Avenue', $user->getLaneType());
        $this->assertSame('Du Test', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertCount(0, $user->getLicenses());
        $this->assertSame('0123456789', $user->getLicenseNumber());
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
    }

    public function testDeleteUser(): void
    {
        $user = UserFactory::createOne()->_disableAutoRefresh();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/user/'.$user->getId());

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/admin/user');
        UserFactory::repository()->assert()->notExists($user);
    }
}
