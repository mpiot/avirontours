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

namespace App\Tests\Functional;

use App\Entity\Season;
use App\Tests\AppWebTestCase;

class SeasonControllerTest extends AppWebTestCase
{
    public function testIndexSeasons()
    {
        $client = static::createClient();
        $url = '/season/';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public function testNewSeason()
    {
        $client = static::createClient();
        $url = '/season/new';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'season[name]' => '',
            'season[licenseEndAt]' => '2020-10-03',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="season_name"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        $client->submitForm('Sauver', [
            'season[name]' => 2030,
            'season[licenseEndAt]' => '2030-10-15',
        ]);
        $this->assertResponseRedirects();
        $season = $this->getEntityManager()->getRepository(Season::class)->findOneBy(['name' => 2030]);
        $this->assertInstanceOf(Season::class, $season);
        $this->assertSame('2030-10-15', $season->getLicenseEndAt()->format('Y-m-d'));
    }

    public function testEditSeason()
    {
        $client = static::createClient();
        $url = '/season/1/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'season[name]' => 2030,
            'season[licenseEndAt]' => '2030-10-15',
        ]);
        $this->assertResponseRedirects();
        $season = $this->getEntityManager()->getRepository(Season::class)->find(1);
        $this->assertSame(2030, $season->getName());
        $this->assertSame('2030-10-15', $season->getLicenseEndAt()->format('Y-m-d'));
    }

    public function testDeleteSeason()
    {
        $client = static::createClient();
        $url = '/season/2/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');
        $this->assertResponseRedirects('/season/');
        $group = $this->getEntityManager()->getRepository(Season::class)->find(2);
        $this->assertNull($group);
    }
}
