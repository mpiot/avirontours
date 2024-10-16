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

use App\Factory\ShellDamageCategoryFactory;
use App\Factory\ShellDamageFactory;
use App\Factory\ShellFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ShellDamageControllerTest extends AppWebTestCase
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
            $damage = ShellDamageFactory::createOne();
            $url = str_replace('{id}', (string) $damage->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider(): \Generator
    {
        yield ['GET', '/admin/shell-damage'];
        yield ['GET', '/admin/shell-damage/new'];
        yield ['POST', '/admin/shell-damage/new'];
        yield ['GET', '/admin/shell-damage/{id}/edit'];
        yield ['POST', '/admin/shell-damage/{id}/edit'];
        yield ['POST', '/admin/shell-damage/{id}'];
    }

    public function testIndexShellDamages(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage');

        $this->assertResponseIsSuccessful();
    }

    public function testNewShellDamage(): void
    {
        $shell = ShellFactory::createOne();
        $category = ShellDamageCategoryFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage/new');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'shell_damage[shell]' => $shell->getId(),
            'shell_damage[category]' => $category->getId(),
            'shell_damage[description]' => 'My description',
            'shell_damage[note]' => 'My note',
            'shell_damage[repairStartAt]' => '2020-09-01',
            'shell_damage[repairEndAt]' => '2020-09-15',
        ]);

        $this->assertResponseRedirects();

        $damage = ShellDamageFactory::repository()->findOneBy(['description' => 'My description']);

        $this->assertSame($shell->getId(), $damage->getshell()->getId());
        $this->assertSame($category->getId(), $damage->getCategory()->getId());
        $this->assertSame('My description', $damage->getDescription());
        $this->assertSame('My note', $damage->getNote());
        $this->assertSame('2020-09-01', $damage->getRepairStartAt()->format('Y-m-d'));
        $this->assertSame('2020-09-15', $damage->getRepairEndAt()->format('Y-m-d'));
    }

    public function testNewShellDamageWithoutData(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'shell_damage[shell]' => '',
            'shell_damage[category]' => '',
            'shell_damage[description]' => '',
            'shell_damage[note]' => '',
            'shell_damage[repairStartAt]' => '',
            'shell_damage[repairEndAt]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#shell_damage_shell')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#shell_damage_category')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(2, $crawler->filter('.invalid-feedback'));
        ShellDamageFactory::repository()->assert()->count(0);
    }

    public function testEditShellDamage(): void
    {
        $damage = ShellDamageFactory::createOne();
        $shell = ShellFactory::createOne();
        $category = ShellDamageCategoryFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage/'.$damage->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'shell_damage[shell]' => $shell->getId(),
            'shell_damage[category]' => $category->getId(),
            'shell_damage[description]' => 'A modified description',
            'shell_damage[note]' => 'A modified note',
            'shell_damage[repairStartAt]' => '2020-01-01',
            'shell_damage[repairEndAt]' => '2020-02-01',
        ]);

        $this->assertResponseRedirects();
        $this->assertSame($shell->getId(), $damage->getshell()->getId());
        $this->assertSame($category->getId(), $damage->getCategory()->getId());
        $this->assertSame('A modified description', $damage->getDescription());
        $this->assertSame('A modified note', $damage->getNote());
        $this->assertSame('2020-01-01', $damage->getRepairStartAt()->format('Y-m-d'));
        $this->assertSame('2020-02-01', $damage->getRepairEndAt()->format('Y-m-d'));
    }

    public function testDeleteShellDamage(): void
    {
        $damage = ShellDamageFactory::createOne()->_disableAutoRefresh();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage/'.$damage->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/admin/shell-damage');

        ShellDamageFactory::repository()->assert()->notExists($damage);
    }
}
