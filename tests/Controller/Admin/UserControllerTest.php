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
use App\Tests\AppWebTestCase;

class UserControllerTest extends AppWebTestCase
{
    public function testIndexUsers()
    {
        $client = static::createClient();
        $url = '/admin/user/';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'super-admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public function testShowUser()
    {
        $client = static::createClient();
        $url = '/admin/user/3';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'super-admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public function testNewUser()
    {
        $client = static::createClient();
        $url = '/admin/user/new';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'super-admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'user[subscriptionDate]' => '',
            'user[firstName]' => '',
            'user[lastName]' => '',
            'user[email]' => '',
            'user[phoneNumber]' => '',
            'user[plainPassword]' => '',
            'user[birthday]' => '',
            'user[postalCode]' => '',
            'user[city]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_subscriptionDate"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_gender')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_firstName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_lastName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_email"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_plainPassword"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_birthday"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_postalCode"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_city"] .form-error-message')->text());
        $this->assertCount(9, $crawler->filter('.form-error-message'));

        $crawler = $client->submitForm('Sauver', [
            'user[subscriptionDate]' => '2019-09-01',
            'user[gender]' => 'm',
            'user[firstName]' => 'John',
            'user[lastName]' => 'Doe',
            'user[email]' => 'john.doe@avirontours.fr',
            'user[phoneNumber]' => '0102030405',
            'user[plainPassword]' => 'engage',
            'user[birthday]' => '2010-01-01',
            'user[postalCode]' => '01000',
            'user[city]' => 'One City',
            'user[rowerCategory]' => User::ROWER_CATEGORY_A,
            'user[licenseNumber]' => '0123456789',
        ]);
        $this->assertResponseRedirects();
        /** @var User $user */
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy(['email' => 'john.doe@avirontours.fr']);
        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('2019-09-01', $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame('m', $user->getGender());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('0102030405', $user->getPhoneNumber());
        $this->assertNotNull($user->getPassword());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertSame(User::ROWER_CATEGORY_A, $user->getRowerCategory());
        $this->assertSame('0123456789', $user->getLicenseNumber());
    }

    public function testEditUser()
    {
        $client = static::createClient();
        $url = '/admin/user/5/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'super-admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'user_edit[subscriptionDate]' => '2019-09-01',
            'user_edit[gender]' => 'm',
            'user_edit[firstName]' => 'John',
            'user_edit[lastName]' => 'Doe',
            'user_edit[email]' => 'john.doe@avirontours.fr',
            'user_edit[phoneNumber]' => '0102030405',
            'user_edit[birthday]' => '2010-01-01',
            'user_edit[postalCode]' => '01000',
            'user_edit[city]' => 'One City',
            'user_edit[rowerCategory]' => User::ROWER_CATEGORY_A,
            'user_edit[licenseNumber]' => '0123456789',
        ]);
        $this->assertResponseRedirects();
        $user = $this->getEntityManager()->getRepository(User::class)->find(5);
        $this->assertSame('2019-09-01', $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame('m', $user->getGender());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame('0102030405', $user->getPhoneNumber());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertSame(User::ROWER_CATEGORY_A, $user->getRowerCategory());
        $this->assertCount(2, $user->getLicenses());
        $this->assertSame('0123456789', $user->getLicenseNumber());
    }

    public function testDeleteUser()
    {
        $client = static::createClient();
        $url = '/admin/user/1';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'super-admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');
        $this->assertResponseRedirects('/admin/user/');
        $group = $this->getEntityManager()->getRepository(User::class)->find(1);
        $this->assertNull($group);
    }
}
