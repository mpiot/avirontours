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

use App\Factory\ShellFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ShellControllerTest extends AppWebTestCase
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
            $shell = ShellFactory::createOne();
            $url = str_replace('{id}', $shell->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider()
    {
        yield ['GET', '/admin/shell'];
        yield ['GET', '/admin/shell/{id}'];
        yield ['GET', '/admin/shell/new'];
        yield ['POST', '/admin/shell/new'];
        yield ['GET', '/admin/shell/{id}/edit'];
        yield ['POST', '/admin/shell/{id}/edit'];
        yield ['DELETE', '/admin/shell/{id}'];
    }

    public function testIndexShells()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell');

        $this->assertResponseIsSuccessful();
    }

    public function testShowShell()
    {
        $shell = ShellFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell/'.$shell->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testNewShell()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell/new');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'shell[name]' => 'A new shell',
            'shell[numberRowers]' => 2,
            'shell[mileage]' => 1000.0,
        ]);

        $this->assertResponseRedirects();

        $shell = ShellFactory::repository()->findOneBy(['name' => 'A new shell']);

        $this->assertSame(2, $shell->getNumberRowers());
        $this->assertSame(1000.0, $shell->getMileage());
        $this->assertSame('2x/2-', $shell->getAbbreviation());
    }

    public function testNewShellWithoutData()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell/new');
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

        ShellFactory::repository()->assertCount(0);
    }

    public function testEditShell()
    {
        $shell = ShellFactory::createOne([
            'numberRowers' => 2,
            'coxed' => false,
            'yolette' => false,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell/'.$shell->getId().'/edit');

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

        $shell->refresh();

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
        $shell = ShellFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_MATERIAL_ADMIN');
        $client->request('GET', '/admin/shell/'.$shell->getId());

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/admin/shell');

        ShellFactory::repository()->assertNotExists($shell);
    }
}
