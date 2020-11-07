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

use App\Entity\ShellDamageCategory;
use App\Factory\ShellDamageCategoryFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ShellDamageCategoryControllerTest extends AppWebTestCase
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
            $category = ShellDamageCategoryFactory::new()->create();
            $url = str_replace('{id}', $category->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider()
    {
        yield ['GET', '/admin/shell-damage-category'];
        yield ['GET', '/admin/shell-damage-category/new'];
        yield ['POST', '/admin/shell-damage-category/new'];
        yield ['GET', '/admin/shell-damage-category/{id}/edit'];
        yield ['POST', '/admin/shell-damage-category/{id}/edit'];
        yield ['DELETE', '/admin/shell-damage-category/{id}'];
    }

    public function testIndexShellDamageCategorys()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage-category');

        $this->assertResponseIsSuccessful();
    }

    public function testNewShellDamageCategory()
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

    public function testNewShellDamageCategoryWithoutData()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage-category/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'shell_damage_category[name]' => '',
        ]);

        $this->assertResponseIsSuccessful();

        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="shell_damage_category_name"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        ShellDamageCategoryFactory::repository()->assertCount(0);
    }

    public function testEditShellDamageCategory()
    {
        $category = ShellDamageCategoryFactory::new()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage-category/'.$category->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'shell_damage_category[priority]' => ShellDamageCategory::PRIORITY_MEDIUM,
            'shell_damage_category[name]' => 'A modified shell damage category',
        ]);

        $this->assertResponseRedirects();

        $category->refresh();

        $this->assertSame('A modified shell damage category', $category->getName());
        $this->assertSame(ShellDamageCategory::PRIORITY_MEDIUM, $category->getPriority());
    }

    public function testDeleteShellDamageCategory()
    {
        $category = ShellDamageCategoryFactory::new()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell-damage-category/'.$category->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/admin/shell-damage-category');

        ShellDamageCategoryFactory::repository()->assertNotExists($category);
    }
}
