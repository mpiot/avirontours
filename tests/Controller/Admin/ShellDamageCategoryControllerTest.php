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
use App\Tests\AppWebTestCase;

class ShellDamageCategoryControllerTest extends AppWebTestCase
{
    public function testIndexShellDamageCategorys()
    {
        $client = static::createClient();
        $url = '/admin/shell-damage-category/';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public function testNewShellDamageCategory()
    {
        $client = static::createClient();
        $url = '/admin/shell-damage-category/new';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'shell_damage_category[name]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="shell_damage_category_name"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        $client->submitForm('Sauver', [
            'shell_damage_category[priority]' => ShellDamageCategory::PRIORITY_MEDIUM,
            'shell_damage_category[name]' => 'A new shell damage',
        ]);
        $this->assertResponseRedirects();
        $shellDamageCategory = $this->getEntityManager()->getRepository(ShellDamageCategory::class)->findOneBy(['name' => 'A new shell damage']);
        $this->assertInstanceOf(ShellDamageCategory::class, $shellDamageCategory);
        $this->assertSame(ShellDamageCategory::PRIORITY_MEDIUM, $shellDamageCategory->getPriority());
    }

    public function testEditShellDamageCategory()
    {
        $client = static::createClient();
        $url = '/admin/shell-damage-category/1/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'shell_damage_category[priority]' => ShellDamageCategory::PRIORITY_MEDIUM,
            'shell_damage_category[name]' => 'A modified shell damage category',
        ]);
        $this->assertResponseRedirects();
        $shellDamageCategory = $this->getEntityManager()->getRepository(ShellDamageCategory::class)->find(1);
        $this->assertSame('A modified shell damage category', $shellDamageCategory->getName());
        $this->assertSame(ShellDamageCategory::PRIORITY_MEDIUM, $shellDamageCategory->getPriority());
    }

    public function testDeleteShellDamageCategory()
    {
        $client = static::createClient();
        $url = '/admin/shell-damage-category/3/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');
        $this->assertResponseRedirects('/admin/shell-damage-category/');
        $group = $this->getEntityManager()->getRepository(ShellDamageCategory::class)->find(3);
        $this->assertNull($group);
    }
}
