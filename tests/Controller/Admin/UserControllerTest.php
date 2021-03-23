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

namespace App\Tests\Controller\Admin;

use App\Entity\User;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AppWebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForAnonymousUser($method, $url)
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForRegularUser($method, $url)
    {
        if (mb_strpos($url, '{id}')) {
            $user = UserFactory::createOne();
            $url = str_replace('{id}', $user->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider()
    {
        yield ['GET', '/admin/user'];
        yield ['GET', '/admin/user/{id}'];
        yield ['GET', '/admin/user/new'];
        yield ['POST', '/admin/user/new'];
        yield ['GET', '/admin/user/{id}/edit'];
        yield ['POST', '/admin/user/{id}/edit'];
        yield ['DELETE', '/admin/user/{id}'];
    }

    public function testIndexUsers()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/user');

        $this->assertResponseIsSuccessful();
    }

    public function testShowUser()
    {
        $user = UserFactory::createOne();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/user/'.$user->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testNewUser()
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/user/new');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'user[subscriptionDate]' => '2019-09-01',
            'user[gender]' => 'm',
            'user[firstName]' => 'John',
            'user[lastName]' => 'Doe',
            'user[email]' => 'john.doe@avirontours.fr',
            'user[phoneNumber]' => '0102030405',
            'user[birthday]' => '2010-01-01',
            'user[address][laneNumber]' => '100',
            'user[address][laneType]' => 'Avenue',
            'user[address][laneName]' => 'du test',
            'user[address][postalCode]' => '01000',
            'user[address][city]' => 'One City',
            'user[rowerCategory]' => User::ROWER_CATEGORY_A,
            'user[licenseNumber]' => '0123456789',
        ]);

        $this->assertResponseRedirects();

        $user = UserFactory::repository()->findOneBy(['email' => 'john.doe@avirontours.fr']);

        $this->assertSame('2019-09-01', $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame('m', $user->getGender());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('0102030405', $user->getPhoneNumber());
        $this->assertNull($user->getPassword());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('100', $user->getLaneNumber());
        $this->assertSame('Avenue', $user->getLaneType());
        $this->assertSame('Du Test', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertSame(User::ROWER_CATEGORY_A, $user->getRowerCategory());
        $this->assertSame('0123456789', $user->getLicenseNumber());
    }

    public function testNewUserWithoutData()
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
            'user[birthday]' => '',
            'user[address][laneNumber]' => '',
            'user[address][laneType]' => '',
            'user[address][laneName]' => '',
            'user[address][postalCode]' => '',
            'user[address][city]' => '',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_subscriptionDate"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_gender')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_firstName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_lastName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_email"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_birthday"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_address_laneNumber"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('label[for="user_address_laneType"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_address_laneName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_address_postalCode"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_address_city"] .form-error-message')->text());
        $this->assertCount(11, $crawler->filter('.form-error-message'));
    }

    public function testEditUser()
    {
        $user = UserFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/user/'.$user->getId().'/edit');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'user_edit[subscriptionDate]' => '2019-09-01',
            'user_edit[gender]' => 'm',
            'user_edit[firstName]' => 'John',
            'user_edit[lastName]' => 'Doe',
            'user_edit[email]' => 'john.doe@avirontours.fr',
            'user_edit[phoneNumber]' => '0102030405',
            'user_edit[birthday]' => '2010-01-01',
            'user_edit[address][laneNumber]' => '100',
            'user_edit[address][laneType]' => 'Avenue',
            'user_edit[address][laneName]' => 'du test',
            'user_edit[address][postalCode]' => '01000',
            'user_edit[address][city]' => 'One City',
            'user_edit[rowerCategory]' => User::ROWER_CATEGORY_A,
            'user_edit[licenseNumber]' => '0123456789',
        ]);

        $this->assertResponseRedirects();

        $user->refresh();

        $this->assertSame('2019-09-01', $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame('m', $user->getGender());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame('0102030405', $user->getPhoneNumber());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('100', $user->getLaneNumber());
        $this->assertSame('Avenue', $user->getLaneType());
        $this->assertSame('Du Test', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertSame(User::ROWER_CATEGORY_A, $user->getRowerCategory());
        $this->assertCount(0, $user->getLicenses());
        $this->assertSame('0123456789', $user->getLicenseNumber());
    }

    public function testDeleteUser()
    {
        $user = UserFactory::createOne();
        $userId = $user->getId();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/user/'.$user->getId());

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/admin/user');
        UserFactory::repository()->assert()->notExists(['id' => $userId]);
    }
}
