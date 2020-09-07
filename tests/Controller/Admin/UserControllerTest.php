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
            'user[plainPassword]' => '',
            'user[birthday]' => '',
            'user[legalRepresentative]' => '',
            'user[address][laneNumber]' => '',
            'user[address][laneType]' => '',
            'user[address][laneName]' => '',
            'user[address][postalCode]' => '',
            'user[address][city]' => '',
            'user[address][phoneNumber]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_subscriptionDate"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#user_gender')->previousAll()->filter('legend')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_firstName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_lastName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_email"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_plainPassword"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_birthday"] .form-error-message')->text());
        $this->assertStringContainsString('Le membre est mineur, merci de renseigner un représentant légal.', $crawler->filter('label[for="user_legalRepresentative"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_address_laneName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_address_laneName"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('label[for="user_address_laneType"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_address_postalCode"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_address_city"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="user_address_phoneNumber"] .form-error-message')->text());
        $this->assertCount(14, $crawler->filter('.form-error-message'));

        $crawler = $client->submitForm('Sauver', [
            'user[subscriptionDate]' => '2019-09-01',
            'user[gender]' => 'm',
            'user[firstName]' => 'John',
            'user[lastName]' => 'Doe',
            'user[email]' => 'john.doe@avirontours.fr',
            'user[plainPassword]' => 'engage',
            'user[birthday]' => '2010-01-01',
            'user[legalRepresentative]' => 'Miss Doe',
            'user[address][laneNumber]' => '999',
            'user[address][laneType]' => 'Rue',
            'user[address][laneName]' => 'De Ouf',
            'user[address][postalCode]' => '01000',
            'user[address][city]' => 'One City',
            'user[address][phoneNumber]' => '0102030405',
            'user[rowerCategory]' => User::ROWER_CATEGORY_A,
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
        $this->assertNotNull($user->getPassword());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('Miss Doe', $user->getLegalRepresentative());
        $this->assertSame('999', $user->getLaneNumber());
        $this->assertSame('Rue', $user->getLaneType());
        $this->assertSame('De Ouf', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertSame('0102030405', $user->getPhoneNumber());
        $this->assertSame(User::ROWER_CATEGORY_A, $user->getRowerCategory());
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
            'user_edit[birthday]' => '2010-01-01',
            'user_edit[legalRepresentative]' => 'Miss Doe',
            'user_edit[address][laneNumber]' => '999',
            'user_edit[address][laneType]' => 'Rue',
            'user_edit[address][laneName]' => 'De Ouf',
            'user_edit[address][postalCode]' => '01000',
            'user_edit[address][city]' => 'One City',
            'user_edit[address][phoneNumber]' => '0102030405',
            'user_edit[rowerCategory]' => User::ROWER_CATEGORY_A,
        ]);
        $this->assertResponseRedirects();
        $user = $this->getEntityManager()->getRepository(User::class)->find(5);
        $this->assertSame('2019-09-01', $user->getSubscriptionDate()->format('Y-m-d'));
        $this->assertSame('m', $user->getGender());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertSame('john.doe', $user->getUsername());
        $this->assertSame('john.doe@avirontours.fr', $user->getEmail());
        $this->assertSame('2010-01-01', $user->getBirthday()->format('Y-m-d'));
        $this->assertSame('Miss Doe', $user->getLegalRepresentative());
        $this->assertSame('999', $user->getLaneNumber());
        $this->assertSame('Rue', $user->getLaneType());
        $this->assertSame('De Ouf', $user->getLaneName());
        $this->assertSame('01000', $user->getPostalCode());
        $this->assertSame('One City', $user->getCity());
        $this->assertSame('0102030405', $user->getPhoneNumber());
        $this->assertSame(User::ROWER_CATEGORY_A, $user->getRowerCategory());
        $this->assertCount(2, $user->getLicenses());
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
