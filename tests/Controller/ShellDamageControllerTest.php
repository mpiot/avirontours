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

namespace App\Tests\Controller;

use App\Entity\ShellDamage;
use App\Tests\AppWebTestCase;

class ShellDamageControllerTest extends AppWebTestCase
{
    public function testIndexShellDamages()
    {
        $client = static::createClient();
        $url = '/shell-damage/';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public function testNewShellDamage()
    {
        $client = static::createClient();
        $url = '/shell-damage/new';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'shell_damage[shell]' => '',
            'shell_damage[category]' => '',
            'shell_damage[description]' => '',
            'shell_damage[repairAt]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('label[for="shell_damage_shell"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('label[for="shell_damage_category"] .form-error-message')->text());
        $this->assertCount(2, $crawler->filter('.form-error-message'));

        $client->submitForm('Sauver', [
            'shell_damage[shell]' => 1,
            'shell_damage[category]' => 2,
            'shell_damage[description]' => 'My description',
        ]);
        $this->assertResponseRedirects();
        $shellDamage = $this->getEntityManager()->getRepository(ShellDamage::class)->findOneBy(['description' => 'My description']);
        $this->assertInstanceOf(ShellDamage::class, $shellDamage);
        $this->assertSame(1, $shellDamage->getshell()->getId());
        $this->assertSame(2, $shellDamage->getCategory()->getId());
        $this->assertNull($shellDamage->getRepairAt());
    }

    public function testEditShellDamage()
    {
        $client = static::createClient();
        $url = '/shell-damage/1/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'shell_damage[shell]' => 1,
            'shell_damage[category]' => 2,
            'shell_damage[description]' => 'A modified description',
            'shell_damage[repairAt]' => '2020-01-01',
        ]);
        $this->assertResponseRedirects();
        $shellDamage = $this->getEntityManager()->getRepository(ShellDamage::class)->find(1);
        $this->assertSame('A modified description', $shellDamage->getDescription());
        $this->assertSame(1, $shellDamage->getshell()->getId());
        $this->assertSame(2, $shellDamage->getCategory()->getId());
        $this->assertSame('2020-01-01', $shellDamage->getRepairAt()->format('Y-m-d'));
    }

    public function testDeleteShellDamage()
    {
        $client = static::createClient();
        $url = '/shell-damage/1/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');
        $this->assertResponseRedirects('/shell-damage/');
        $group = $this->getEntityManager()->getRepository(ShellDamage::class)->find(1);
        $this->assertNull($group);
    }
}
