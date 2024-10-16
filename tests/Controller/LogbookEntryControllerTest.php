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

namespace App\Tests\Controller;

use App\Entity\Training;
use App\Enum\SportType;
use App\Factory\LicenseFactory;
use App\Factory\LogbookEntryFactory;
use App\Factory\ShellDamageCategoryFactory;
use App\Factory\ShellDamageFactory;
use App\Factory\ShellFactory;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;

class LogbookEntryControllerTest extends AppWebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForAnonymousUser($method, $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $logbookEntry = LogbookEntryFactory::new()->notFinished()->withoutDamages()->create();
            $url = str_replace('{id}', (string) $logbookEntry->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForUnlicensedUser($method, $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $logbookEntry = LogbookEntryFactory::new()->notFinished()->withoutDamages()->create();
            $url = str_replace('{id}', (string) $logbookEntry->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider(): \Generator
    {
        yield ['GET', '/logbook-entry'];
        yield ['GET', '/logbook-entry/new'];
        yield ['POST', '/logbook-entry/new'];
        yield ['GET', '/logbook-entry/{id}/finish'];
        yield ['POST', '/logbook-entry/{id}/finish'];
        yield ['GET', '/logbook-entry/statistics'];
        yield ['GET', '/logbook-entry/{id}/edit'];
        yield ['POST', '/logbook-entry/{id}/edit'];
        yield ['POST', '/logbook-entry/{id}'];
    }

    public function testIndexLogbookEntries(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry');

        $this->assertResponseIsSuccessful();
    }

    public function testNewLogbookEntry(): void
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false]);
        $licences = LicenseFactory::new()->annualActive()->withValidLicense()->many(2)->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($licences[0]->getUser());
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shell->getId(),
            'logbook_entry_start[crewMembers]' => [$licences[0]->getUser()->getId(), $licences[1]->getUser()->getId()],
            'logbook_entry_start[startAt]' => '09:00',
        ]);

        $this->assertResponseRedirects();

        $logBookEntry = LogbookEntryFactory::repository()->last();

        $this->assertSame($shell->getId(), $logBookEntry->getShell()->getId());
        $this->assertCount(2, $logBookEntry->getCrewMembers());
        $this->assertSame((new \DateTime())->format('d/m/Y'), $logBookEntry->getDate()->format('d/m/Y'));
        $this->assertSame('09:00', $logBookEntry->getStartAt()->format('H:i'));
        $this->assertNull($logBookEntry->getEndAt());
        $this->assertNull($logBookEntry->getCoveredDistance());
        $this->assertEmpty($logBookEntry->getShellDamages());
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testNewLogbookEntryWithAutomaticTraining(): void
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false]);
        $licences = LicenseFactory::new([
            'user' => UserFactory::new([
                'automaticTraining' => true,
            ]),
        ])->annualActive()->withValidLicense()->many(2)->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($licences[0]->getUser());
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shell->getId(),
            'logbook_entry_start[crewMembers]' => [$licences[0]->getUser()->getId(), $licences[1]->getUser()->getId()],
            'logbook_entry_start[startAt]' => '09:00',
        ]);

        $this->assertResponseRedirects();

        $logBookEntry = LogbookEntryFactory::repository()->last();

        $this->assertSame($shell->getId(), $logBookEntry->getShell()->getId());
        $this->assertCount(2, $logBookEntry->getCrewMembers());
        $this->assertSame((new \DateTime())->format('d/m/Y'), $logBookEntry->getDate()->format('d/m/Y'));
        $this->assertSame('09:00', $logBookEntry->getStartAt()->format('H:i'));
        $this->assertNull($logBookEntry->getEndAt());
        $this->assertNull($logBookEntry->getCoveredDistance());
        $this->assertEmpty($logBookEntry->getShellDamages());
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testNewLogbookEntryWithoutData(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => '',
            'logbook_entry_start[startAt]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#logbook_entry_start_shell')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#logbook_entry_start_startAt')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(2, $crawler->filter('.invalid-feedback'));
        LogbookEntryFactory::repository()->assert()->count(0);
    }

    public function testNewLogbookEntryInvalidCrewSize(): void
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false]);
        $licence = LicenseFactory::new()->annualActive()->withValidLicense()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shell->getId(),
            'logbook_entry_start[crewMembers]' => [$licence->getUser()->getId()],
            'logbook_entry_start[startAt]' => '9:00',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Le nombre de membre d\'équipage ne correspond pas au nombre de place.', $crawler->filter('#logbook_entry_start_crewMembers')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        LogbookEntryFactory::repository()->assert()->count(0);
    }

    public function testNewLogbookEntryWithCrewMemberOnWater(): void
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false]);
        $licences = LicenseFactory::new()->annualActive()->withValidLicense()->many(2)->create();
        LogbookEntryFactory::new()->notFinished()->create([
            'shell' => ShellFactory::new(['numberRowers' => 1, 'coxed' => false]),
            'crewMembers' => [$licences[0]->getUser()],
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shell->getId(),
            'logbook_entry_start[crewMembers]' => [$licences[0]->getUser()->getId(), $licences[1]->getUser()->getId()],
            'logbook_entry_start[startAt]' => '9:00',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString(\sprintf('Certains membres d\'équipage sont déjà sortis: %s.', $licences[0]->getUser()->getFullName()), $crawler->filter('#logbook_entry_start_crewMembers')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        LogbookEntryFactory::repository()->assert()->count(1);
    }

    public function testNewLogbookEntryWithInvalidLogbookEntryLimit(): void
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false]);
        $licences = LicenseFactory::new()->annualActive()->withValidLicense()->many(2)->create(['logbookEntryLimit' => 0]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shell->getId(),
            'logbook_entry_start[crewMembers]' => [$licences[0]->getUser()->getId(), $licences[1]->getUser()->getId()],
            'logbook_entry_start[startAt]' => '9:00',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Certains membres d\'équipage ont atteint leur limite de nombre de sorties:', $crawler->filter('#logbook_entry_start_crewMembers')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        LogbookEntryFactory::repository()->assert()->count(0);
    }

    public function testNewLogbookEntryWithShellOnWater(): void
    {
        $shell = ShellFactory::createOne(['numberRowers' => 1, 'coxed' => false]);
        $license = LicenseFactory::new()->annualActive()->withValidLicense()->create();
        LogbookEntryFactory::new()->withActiveCrew(1)->withoutDamages()->notFinished()->create([
            'shell' => $shell,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shell->getId(),
            'logbook_entry_start[crewMembers]' => [$license->getUser()->getId()],
            'logbook_entry_start[startAt]' => '9:00',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Ce bâteau est déjà sorti.', $crawler->filter('#logbook_entry_start_shell')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        LogbookEntryFactory::repository()->assert()->count(1);
    }

    public function testNewLogbookEntryWithDisabledShell(): void
    {
        $shell = ShellFactory::createOne(['name' => 'My shell', 'enabled' => false]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $crawler = $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();
        $this->assertEmpty($crawler->filterXPath(\sprintf('//select[@id="logbook_entry_start_shell"]/option[@value="%s"]', $shell->getId())));
    }

    public function testNewLogbookEntryWithHighlyDamagedShell(): void
    {
        $damage = ShellDamageFactory::new()->highlyDamaged()->notRepaired()->create([
            'shell' => ShellFactory::createOne(['numberRowers' => 1, 'coxed' => false]),
        ]);
        $license = LicenseFactory::new()->annualActive()->withValidLicense()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $damage->getShell()->getId(),
            'logbook_entry_start[crewMembers]' => [$license->getUser()->getId()],
            'logbook_entry_start[startAt]' => '9:00',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Ce bâteau est endommagé.', $crawler->filter('#logbook_entry_start_shell')->ancestors()->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        LogbookEntryFactory::repository()->assert()->count(0);
    }

    public function testNewLogbookEntryWithMediumDamagedShell(): void
    {
        $shell = ShellFactory::createOne(['numberRowers' => 1, 'coxed' => false]);
        $shellDamage = ShellDamageFactory::new()->mediumDamaged()->create(['shell' => $shell]);
        $license = LicenseFactory::new()->annualActive()->withValidLicense()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shellDamage->getShell()->getId(),
            'logbook_entry_start[crewMembers]' => [$license->getUser()->getId()],
            'logbook_entry_start[startAt]' => '9:00',
        ]);

        $this->assertResponseRedirects();
        LogbookEntryFactory::repository()->assert()->count(1);
    }

    public function testNewLogbookEntryWithNonUserCrewMember(): void
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false]);
        $license = LicenseFactory::new()->annualInactive()->withInvalidLicense()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/new');

        $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shell->getId(),
            'logbook_entry_start[crewMembers]' => [$license->getUser()->getId()],
            'logbook_entry_start[nonUserCrewMembers]' => 'John Doe',
            'logbook_entry_start[startAt]' => '09:00',
        ]);

        $this->assertResponseRedirects();

        $logBookEntry = LogbookEntryFactory::repository()->last();

        $this->assertCount(1, $logBookEntry->getCrewMembers());
        $this->assertCount(1, $logBookEntry->getNonUserCrewMembers());
    }

    public function testNewLogbookEntryWithNonUserCrewMemberNoAvailableForUser(): void
    {
        $license = LicenseFactory::new()->annualActive()->withValidLicense()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($license->getUser());
        $crawler = $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('#logbook_entry_start_nonUserCrewMembers'));
    }

    public function testNewLogbookEntryWithOnlyNonUserCrewMembers(): void
    {
        $shell = ShellFactory::new(['numberRowers' => 2, 'coxed' => false])->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shell->getId(),
            'logbook_entry_start[crewMembers]' => [],
            'logbook_entry_start[nonUserCrewMembers]' => 'John Doe, Foo Bar',
            'logbook_entry_start[startAt]' => '09:00',
        ]);

        $this->assertResponseRedirects();

        $logBookEntry = LogbookEntryFactory::repository()->last();

        $this->assertCount(0, $logBookEntry->getCrewMembers());
        $this->assertCount(2, $logBookEntry->getNonUserCrewMembers());
    }

    public function testUserListLogbookEntryFormAsAdmin(): void
    {
        LicenseFactory::new()->annualActive()->withValidLicense()->many(1)->create();
        LicenseFactory::new()->annualActive()->withInvalidLicense()->many(2)->create();
        LicenseFactory::new()->annualInactive()->many(4)->create();
        UserFactory::createMany(8);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $crawler = $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();
        $this->assertCount(16, $crawler->filter('#logbook_entry_start_crewMembers > option'));
    }

    public function testUserListLogbookEntryFormAsUser(): void
    {
        $users = LicenseFactory::new()->annualActive()->withValidLicense()->many(1)->create();
        LicenseFactory::new()->annualActive()->withInvalidLicense()->many(2)->create();
        LicenseFactory::new()->annualInactive()->many(4)->create();
        UserFactory::createMany(8);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($users[0]->getUser());
        $crawler = $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('#logbook_entry_start_crewMembers > option'));
    }

    public function testEditLogbookEntry(): void
    {
        $shell = ShellFactory::new(['numberRowers' => 2, 'coxed' => false])->create();
        $users = UserFactory::createMany(2);
        $entry = LogbookEntryFactory::createOne(['shellDamages' => new ArrayCollection()]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/'.$entry->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'logbook_entry[shell]' => $shell->getId(),
            'logbook_entry[crewMembers]' => [$users[0]->getId(), $users[1]->getId()],
            'logbook_entry[startAt]' => '15:00',
            'logbook_entry[endAt]' => '16:00',
            'logbook_entry[coveredDistance]' => 12.22,
        ]);

        $this->assertResponseRedirects();
        $this->assertSame($shell->getId(), $entry->getShell()->getId());
        $this->assertCount(2, $entry->getCrewMembers());
        $this->assertSame((new \DateTime())->format('d/m/Y'), $entry->getDate()->format('d/m/Y'));
        $this->assertSame('15:00', $entry->getStartAt()->format('H:i'));
        $this->assertSame('16:00', $entry->getEndAt()->format('H:i'));
        $this->assertSame(12.2, $entry->getCoveredDistance());
        $this->assertEmpty($entry->getShellDamages());
        $this->assertSame(12.2, $entry->getShell()->getMileage());
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testEditLogbookEntryWithCrewMemberOnWater(): void
    {
        $license = LicenseFactory::new()->annualActive()->withValidLicense()->create();
        LogbookEntryFactory::new()->notFinished()->create([
            'shell' => ShellFactory::new(['numberRowers' => 1, 'coxed' => false])->create(),
            'crewMembers' => [$license->getUser()],
        ]);
        $entry = LogbookEntryFactory::new()->notFinished()->create([
            'shell' => ShellFactory::new(['numberRowers' => 1, 'coxed' => false])->create(),
            'nonUserCrewMembers' => ['John Doe'],
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/'.$entry->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'logbook_entry[crewMembers]' => [$license->getUser()->getId()],
            'logbook_entry[nonUserCrewMembers]' => '',
        ]);

        $this->assertResponseRedirects();
        $this->assertCount(1, $entry->getCrewMembers());
        $this->assertSame($license->getUser()->getId(), $entry->getCrewMembers()->first()->getId());
        $this->assertCount(0, $entry->getNonUserCrewMembers());
    }

    public function testEditLogbookEntryWithShellOnWater(): void
    {
        $entries = LogbookEntryFactory::new()->notFinished()->many(2)->create([
            'shell' => ShellFactory::new(['numberRowers' => 1, 'coxed' => false])->create(),
            'nonUserCrewMembers' => ['John Doe'],
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/'.$entries[0]->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'logbook_entry[shell]' => $entries[1]->getShell()->getId(),
        ]);

        $this->assertResponseRedirects();
        $this->assertSame($entries[1]->getShell()->getId(), $entries[0]->getShell()->getId());
    }

    public function testEditLogbookEntryWithDamagedShell(): void
    {
        $shellDamage = ShellDamageFactory::new()->highlyDamaged()->create();
        $entry = LogbookEntryFactory::new()->notFinished()->create([
            'shell' => ShellFactory::new(['numberRowers' => 1, 'coxed' => false])->create(),
            'nonUserCrewMembers' => ['John Doe'],
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/'.$entry->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'logbook_entry[shell]' => $shellDamage->getShell()->getId(),
        ]);

        $this->assertResponseRedirects();
        $this->assertSame($shellDamage->getShell()->getId(), $entry->getShell()->getId());
    }

    public function testEditShellLogbookEntry(): void
    {
        $shell = ShellFactory::new(['numberRowers' => 2, 'coxed' => false])->create();
        $entryShell = ShellFactory::new(['numberRowers' => 2, 'coxed' => false])->create();
        $entry = LogbookEntryFactory::createOne([
            'shellDamages' => new ArrayCollection(),
            'shell' => $entryShell,
            'crewMembers' => UserFactory::new()->many($entryShell->getCrewSize()),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/'.$entry->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'logbook_entry[shell]' => $shell->getId(),
            'logbook_entry[crewMembers]' => [$entry->getCrewMembers()->get(0)->getId(), $entry->getCrewMembers()->get(1)->getId()],
            'logbook_entry[startAt]' => '15:00',
            'logbook_entry[endAt]' => '16:00',
            'logbook_entry[coveredDistance]' => 10,
        ]);

        $this->assertResponseRedirects();
        $this->assertSame($shell->getId(), $entry->getShell()->getId());
        $this->assertSame(10.0, $entry->getShell()->getMileage());
        $this->assertSame(0.0, $entryShell->getMileage());
    }

    public function testFinishLogbookEntry(): void
    {
        $entry = LogbookEntryFactory::new()->notFinished()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/'.$entry->getId().'/finish');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Terminer la sortie', [
            'logbook_entry_finish[endAt]' => '16:00',
            'logbook_entry_finish[coveredDistance]' => 12.22,
        ]);

        $this->assertResponseRedirects();
        $this->assertSame('16:00', $entry->getEndAt()->format('H:i'));
        $this->assertSame(12.2, $entry->getCoveredDistance());
        $this->assertSame(12.2, $entry->getShell()->getMileage());
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testFinishLogbookEntryWithAutomaticTraining(): void
    {
        $entry = LogbookEntryFactory::new([
            'crewMembers' => UserFactory::new(['automaticTraining' => true])->many(2),
            'date' => new \DateTime('2022-01-15'),
            'startAt' => new \DateTime('14:30'),
        ])->notFinished()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/'.$entry->getId().'/finish');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Terminer la sortie', [
            'logbook_entry_finish[endAt]' => '16:00',
            'logbook_entry_finish[coveredDistance]' => 12.22,
        ]);

        $this->assertResponseRedirects();
        $this->assertSame('16:00', $entry->getEndAt()->format('H:i'));
        $this->assertSame(12.2, $entry->getCoveredDistance());
        $this->assertSame(12.2, $entry->getShell()->getMileage());
        TrainingFactory::repository()->assert()->count(2);
        /** @var Training $training */
        $training = TrainingFactory::repository()->first();
        $this->assertSame($entry->getCrewMembers()->first(), $training->getUser());
        $this->assertSame('2022-01-15 14:30:00', $training->getTrainedAt()->format('Y-m-d H:i:s'));
        $this->assertSame(12200, $training->getDistance());
        $this->assertSame(54000, $training->getDuration());
        $this->assertSame(SportType::Rowing, $training->getSport());
        $training = TrainingFactory::repository()->last();
        $this->assertSame($entry->getCrewMembers()->last(), $training->getUser());
        $this->assertSame('2022-01-15 14:30:00', $training->getTrainedAt()->format('Y-m-d H:i:s'));
        $this->assertSame(12200, $training->getDistance());
        $this->assertSame(54000, $training->getDuration());
        $this->assertSame(SportType::Rowing, $training->getSport());
    }

    public function testFinishLogbookWithDamageEntry(): void
    {
        $entry = LogbookEntryFactory::new()->notFinished()->withoutDamages()->create();
        $categories = ShellDamageCategoryFactory::createMany(2);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $crawler = $client->request('GET', '/logbook-entry/'.$entry->getId().'/finish');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Terminer la sortie')->form([
            'logbook_entry_finish[endAt]' => '16:00',
            'logbook_entry_finish[coveredDistance]' => 12.22,
        ]);
        $values = $form->getPhpValues();
        $values['logbook_entry_finish']['shellDamages'][0]['category'] = $categories[0]->getId();
        $values['logbook_entry_finish']['shellDamages'][0]['description'] = '';
        $values['logbook_entry_finish']['shellDamages'][1]['category'] = $categories[1]->getId();
        $values['logbook_entry_finish']['shellDamages'][1]['description'] = 'A little description';
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseRedirects();
        $this->assertCount(2, $entry->getShellDamages());
        $this->assertSame($categories[0]->getId(), $entry->getShellDamages()->first()->getCategory()->getId());
        $this->assertNull($entry->getShellDamages()->first()->getDescription());
        $this->assertSame($categories[1]->getId(), $entry->getShellDamages()->last()->getCategory()->getId());
        $this->assertSame('A little description', $entry->getShellDamages()->last()->getDescription());
    }

    public function testDeleteLogbookEntry(): void
    {
        $shell = ShellFactory::createOne();
        $shellDamage = ShellDamageFactory::createOne();
        $entry = LogbookEntryFactory::createOne([
            'shell' => $shell,
            'shellDamages' => [$shellDamage],
        ])->_disableAutoRefresh();
        $shell->_save();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/'.$entry->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/logbook-entry');

        LogbookEntryFactory::repository()->assert()->notExists($entry);
        ShellDamageFactory::assert()->exists($shellDamage);
        $this->assertNull($shellDamage->getLogbookEntry());
        $this->assertSame(0.0, $shell->getMileage());
    }
}
