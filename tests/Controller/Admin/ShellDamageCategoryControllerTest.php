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

use App\Entity\ShellDamageCategory;
use App\Factory\ShellDamageCategoryFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ShellDamageCategoryControllerTest extends AppWebTestCase
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
            $category = ShellDamageCategoryFactory::createOne();
            $url = str_replace('{id}', (string) $category->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider(): \Generator
    {
        yield ['GET', '/admin/shell-damage-category'];
        yield ['GET', '/admin/shell-damage-category/new'];
        yield ['POST', '/admin/shell-damage-category/new'];
        yield ['GET', '/admin/shell-damage-category/{id}/edit'];
        yield ['POST', '/admin/shell-damage-category/{id}/edit'];
        yield ['POST', '/admin/shell-damage-category/{id}'];
    }

    public function testIndexShellDamageCategorys(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage-category');

        $this->assertResponseIsSuccessful();
    }

    public function testNewShellDamageCategory(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage-category/new');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'shell_damage_category[priority]' => ShellDamageCategory::PRIORITY_MEDIUM,
            'shell_damage_category[name]' => 'A new shell damage',
        ]);

        $this->assertResponseRedirects();

        $category = ShellDamageCategoryFactory::repository()->findOneBy(['name' => 'A new shell damage']);

        $this->assertSame('A new shell damage', $category->getName());
        $this->assertSame(ShellDamageCategory::PRIORITY_MEDIUM, $category->getPriority());
    }

    public function testNewShellDamageCategoryWithoutData(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage-category/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'shell_damage_category[name]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#shell_damage_category_name')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        ShellDamageCategoryFactory::repository()->assert()->count(0);
    }

    public function testEditShellDamageCategory(): void
    {
        $category = ShellDamageCategoryFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage-category/'.$category->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'shell_damage_category[priority]' => ShellDamageCategory::PRIORITY_MEDIUM,
            'shell_damage_category[name]' => 'A modified shell damage category',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_SEE_OTHER);
        $this->assertSame('A modified shell damage category', $category->getName());
        $this->assertSame(ShellDamageCategory::PRIORITY_MEDIUM, $category->getPriority());
    }

    public function testDeleteShellDamageCategory(): void
    {
        $category = ShellDamageCategoryFactory::createOne()->_disableAutoRefresh();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage-category/'.$category->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/admin/shell-damage-category');
        ShellDamageCategoryFactory::repository()->assert()->notExists($category);
    }
}
