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

use App\Entity\Shell;
use App\Tests\AppWebTestCase;

class ShellControllerTest extends AppWebTestCase
{
    public function testIndexShells()
    {
        $client = static::createClient();
        $url = '/admin/shell/';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public function testShowShell()
    {
        $client = static::createClient();
        $url = '/admin/shell/1';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public function testNewShell()
    {
        $client = static::createClient();
        $url = '/admin/shell/new';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'shell[name]' => '',
            'shell[numberRowers]' => '',
            'shell[productionYear]' => '',
            'shell[weightCategory]' => '',
            'shell[newPrice]' => '',
            'shell[mileage]' => '',
            'shell[riggerMaterial]' => '',
            'shell[riggerPosition]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="shell_name"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="shell_numberRowers"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="shell_mileage"] .form-error-message')->text());
        $this->assertCount(3, $crawler->filter('.form-error-message'));

        $client->submitForm('Sauver', [
            'shell[name]' => 'A new shell',
            'shell[numberRowers]' => 2,
            'shell[mileage]' => 1000,
        ]);
        $this->assertResponseRedirects();
        $shell = $this->getEntityManager()->getRepository(Shell::class)->findOneBy(['name' => 'A new shell']);
        $this->assertInstanceOf(Shell::class, $shell);
        $this->assertSame(2, $shell->getNumberRowers());
        $this->assertSame(1000.0, $shell->getMileage());
        $this->assertSame('2x/2-', $shell->getAbbreviation());
    }

    public function testEditShell()
    {
        $client = static::createClient();
        $url = '/admin/shell/1/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'shell_edit[name]' => 'A modified shell',
            'shell_edit[numberRowers]' => 8,
            'shell_edit[rowingType]' => 'sweep',
            'shell_edit[coxed]' => true,
            'shell_edit[yolette]' => true,
            'shell_edit[mileage]' => 10,
        ]);
        $this->assertResponseRedirects();
        $shell = $this->getEntityManager()->getRepository(Shell::class)->find(1);
        $this->assertSame('A modified shell', $shell->getName());
        $this->assertSame(2, $shell->getNumberRowers());
        $this->assertSame('sweep', $shell->getRowingType());
        $this->assertFalse($shell->getCoxed());
        $this->assertFalse($shell->getYolette());
        $this->assertSame(10.0, $shell->getMileage());
        $this->assertSame('2-', $shell->getAbbreviation());
    }

    public function testDeleteShell()
    {
        $client = static::createClient();
        $url = '/admin/shell/1';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');
        $this->assertResponseRedirects('/admin/shell/');
        $group = $this->getEntityManager()->getRepository(Shell::class)->find(1);
        $this->assertNull($group);
    }
}
