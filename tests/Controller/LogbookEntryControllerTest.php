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

use App\Entity\LogbookEntry;
use App\Entity\Shell;
use App\Tests\AppWebTestCase;

class LogbookEntryControllerTest extends AppWebTestCase
{
    public function testIndexLogbookEntries()
    {
        $client = static::createClient();
        $url = '/logbook-entry/';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'outdated.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();
    }

    public function testNewLogbookEntry()
    {
        $client = static::createClient();
        $url = '/logbook-entry/new';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'outdated.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'logbook_entry_new[shell]' => '',
            'logbook_entry_new[startAt]' => '',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('label[for="logbook_entry_new_shell"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="logbook_entry_new_startAt"] .form-error-message')->text());
        $this->assertCount(2, $crawler->filter('.form-error-message'));

        $crawler = $client->submitForm('Sauver', [
            'logbook_entry_new[shell]' => 2,
            'logbook_entry_new[crewMembers]' => [3],
            'logbook_entry_new[startAt]' => '9:00',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Le nombre de membre d\'équipage ne correspond pas au nombre de place.', $crawler->filter('label[for="logbook_entry_new_crewMembers"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        $crawler = $client->submitForm('Sauver', [
            'logbook_entry_new[shell]' => 2,
            'logbook_entry_new[crewMembers]' => [3, 5],
            'logbook_entry_new[startAt]' => '9:00',
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Certains membres d\'équipage ne sont pas autorisé sur ce bâteau: C User.', $crawler->filter('label[for="logbook_entry_new_crewMembers"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        $client->submitForm('Sauver', [
            'logbook_entry_new[shell]' => 2,
            'logbook_entry_new[crewMembers]' => [3, 4],
            'logbook_entry_new[startAt]' => '09:00',
        ]);
        $this->assertResponseRedirects();
        $logBookEntry = $this->getEntityManager()->getRepository(LogbookEntry::class)->findOneBy([], ['id' => 'DESC']);
        $this->assertInstanceOf(LogbookEntry::class, $logBookEntry);
        $this->assertSame(2, $logBookEntry->getShell()->getId());
        $this->assertCount(2, $logBookEntry->getCrewMembers());
        $this->assertSame((new \DateTime())->format('d/m/Y'), $logBookEntry->getDate()->format('d/m/Y'));
        $this->assertSame('09:00', $logBookEntry->getStartAt()->format('H:i'));
        $this->assertNull($logBookEntry->getEndAt());
        $this->assertNull($logBookEntry->getCoveredDistance());
        $this->assertEmpty($logBookEntry->getShellDamages());
        $this->assertSame(10.0, $logBookEntry->getShell()->getMileage());

        $crawler = $client->request('GET', $url);
        $this->assertCount(3, $crawler->filter('#logbook_entry_new_shell > option'));
        $this->assertCount(1, $crawler->filter('#logbook_entry_new_crewMembers > option'));
    }

    public function testEditLogbookEntry()
    {
        $client = static::createClient();
        $url = '/logbook-entry/1/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'outdated.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'logbook_entry[shell]' => 2,
            'logbook_entry[crewMembers]' => [3, 4],
            'logbook_entry[startAt]' => '15:00',
            'logbook_entry[endAt]' => '16:00',
            'logbook_entry[coveredDistance]' => 12.22,
        ]);
        $this->assertResponseRedirects();
        $logBookEntry = $this->getEntityManager()->getRepository(LogbookEntry::class)->find(1);
        $this->assertInstanceOf(LogbookEntry::class, $logBookEntry);
        $this->assertSame(2, $logBookEntry->getShell()->getId());
        $this->assertCount(2, $logBookEntry->getCrewMembers());
        $this->assertSame((new \DateTime())->format('d/m/Y'), $logBookEntry->getDate()->format('d/m/Y'));
        $this->assertSame('15:00', $logBookEntry->getStartAt()->format('H:i'));
        $this->assertSame('16:00', $logBookEntry->getEndAt()->format('H:i'));
        $this->assertSame(12.2, $logBookEntry->getCoveredDistance());
        $this->assertEmpty($logBookEntry->getShellDamages());
        $this->assertSame(12.2, $logBookEntry->getShell()->getMileage());
    }

    public function testChangeShellLogbookEntry()
    {
        $client = static::createClient();
        $url = '/logbook-entry/1/edit';

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);

        $client->submitForm('Modifier', [
            'logbook_entry[shell]' => 3,
            'logbook_entry[crewMembers]' => [3, 4],
            'logbook_entry[startAt]' => '15:00',
            'logbook_entry[endAt]' => '16:00',
            'logbook_entry[coveredDistance]' => 10,
        ]);
        $this->assertResponseRedirects();
        $logBookEntry = $this->getEntityManager()->getRepository(LogbookEntry::class)->find(1);
        $this->assertSame(3, $logBookEntry->getShell()->getId());
        $this->assertSame(10.0, $logBookEntry->getShell()->getMileage());

        $previousShell = $this->getEntityManager()->getRepository(Shell::class)->find(2);
        $this->assertSame(0.0, $previousShell->getMileage());
    }

    public function testFinishLogbookEntry()
    {
        $client = static::createClient();
        $url = '/logbook-entry/2/finish';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'outdated.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Terminer la sortie', [
            'logbook_entry_finish[endAt]' => '16:00',
            'logbook_entry_finish[coveredDistance]' => 12.22,
        ]);
        $this->assertResponseRedirects();
        $logBookEntry = $this->getEntityManager()->getRepository(LogbookEntry::class)->find(2);
        $this->assertSame('16:00', $logBookEntry->getEndAt()->format('H:i'));
        $this->assertSame(12.2, $logBookEntry->getCoveredDistance());
        $this->assertSame(12.2, $logBookEntry->getShell()->getMileage());
    }

    public function testFinishLogbookWithDamageEntry()
    {
        $client = static::createClient();
        $url = '/logbook-entry/2/finish';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'outdated.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'a.user');
        $crawler = $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Terminer la sortie')->form([
            'logbook_entry_finish[endAt]' => '16:00',
            'logbook_entry_finish[coveredDistance]' => 12.22,
        ]);
        $values = $form->getPhpValues();
        $values['logbook_entry_finish']['shellDamages'][0]['category'] = 1;
        $values['logbook_entry_finish']['shellDamages'][0]['description'] = '';
        $values['logbook_entry_finish']['shellDamages'][1]['category'] = 2;
        $values['logbook_entry_finish']['shellDamages'][1]['description'] = 'A little description';
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseRedirects();
        $logBookEntry = $this->getEntityManager()->getRepository(LogbookEntry::class)->find(2);
        $this->assertCount(2, $logBookEntry->getShellDamages());
        $this->assertSame(1, $logBookEntry->getShellDamages()->first()->getCategory()->getId());
        $this->assertNull($logBookEntry->getShellDamages()->first()->getDescription());
        $this->assertSame(2, $logBookEntry->getShellDamages()->last()->getCategory()->getId());
        $this->assertSame('A little description', $logBookEntry->getShellDamages()->last()->getDescription());
    }

    public function testDeleteLogbookEntry()
    {
        $client = static::createClient();
        $url = '/logbook-entry/1/edit';

        $client->request('GET', $url);
        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'outdated.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'a.user');
        $client->request('GET', $url);
        $this->assertResponseStatusCodeSame(403);

        $this->logIn($client, 'admin.user');
        $client->request('GET', $url);
        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');
        $this->assertResponseRedirects('/logbook-entry/');
        $logbookEntry = $this->getEntityManager()->getRepository(LogbookEntry::class)->find(1);
        $this->assertNull($logbookEntry);
        $shell = $this->getEntityManager()->getRepository(Shell::class)->find(2);
        $this->assertSame(0.0, $shell->getMileage());
    }
}
