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

use App\Factory\GroupFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class GroupControllerTest extends AppWebTestCase
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
            $group = GroupFactory::createOne();
            $url = str_replace('{id}', (string) $group->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider(): \Generator
    {
        yield ['GET', '/admin/group'];
        yield ['GET', '/admin/group/{id}'];
        yield ['GET', '/admin/group/new'];
        yield ['POST', '/admin/group/new'];
        yield ['GET', '/admin/group/{id}/edit'];
        yield ['POST', '/admin/group/{id}/edit'];
        yield ['POST', '/admin/group/{id}'];
    }

    public function testIndexGroups(): void
    {
        GroupFactory::createMany(20);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/group');

        $this->assertResponseIsSuccessful();
    }

    public function testShowGroup(): void
    {
        $group = GroupFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/group/'.$group->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testNewGroup(): void
    {
        $member1 = UserFactory::createOne(['firstName' => 'A']);
        $member2 = UserFactory::createOne(['firstName' => 'B']);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/group/new');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'group[name]' => 'A new group',
            'group[members]' => [$member2->getId(), $member1->getId()],
        ]);

        $this->assertResponseRedirects();

        $group = GroupFactory::repository()->findOneBy(['name' => 'A new group']);

        $this->assertSame('A new group', $group->getName());
        $this->assertCount(2, $group->getMembers());
        $this->assertSame($member1->getId(), $group->getMembers()->first()->getId());
        $this->assertSame($member2->getId(), $group->getMembers()->last()->getId());
    }

    public function testNewGroupWithoutData(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/group/new');
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'group[name]' => '',
            'group[members]' => [],
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#group_name')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette collection doit contenir 1 élément ou plus.', $crawler->filter('#group_members')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(2, $crawler->filter('.invalid-feedback'));
        GroupFactory::repository()->assert()->count(0);
    }

    public function testEditGroup(): void
    {
        $group = GroupFactory::new()->withMembers()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/group/'.$group->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'group[name]' => 'A modified group',
        ]);

        $this->assertResponseRedirects();
        $this->assertSame('A modified group', $group->getName());
    }

    public function testDeleteGroup(): void
    {
        $group = GroupFactory::createOne()->disableAutoRefresh();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER_ADMIN');
        $client->request('GET', '/admin/group/'.$group->getId());

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/admin/group');
        GroupFactory::repository()->assert()->notExists($group);
    }
}
