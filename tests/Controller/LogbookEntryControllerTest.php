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

use App\Entity\Shell;
use App\Entity\User;
use App\Factory\LicenseFactory;
use App\Factory\LogbookEntryFactory;
use App\Factory\ShellDamageCategoryFactory;
use App\Factory\ShellDamageFactory;
use App\Factory\ShellFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Response;

class LogbookEntryControllerTest extends AppWebTestCase
{
    /**
     * @dataProvider mainAndLogbookSubdomainUrlProvider
     */
    public function testAccessDeniedForAnonymousUser($method, $url)
    {
        if (mb_strpos($url, '{id}')) {
            $logbookEntry = LogbookEntryFactory::new()->notFinished()->withoutDamages()->create();
            $url = str_replace('{id}', $logbookEntry->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    /**
     * @dataProvider mainAndLogbookSubdomainUrlProvider
     */
    public function testAccessDeniedForUnlicensedUser($method, $url)
    {
        if (mb_strpos($url, '{id}')) {
            $logbookEntry = LogbookEntryFactory::new()->notFinished()->withoutDamages()->create();
            $url = str_replace('{id}', $logbookEntry->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @dataProvider onlyMainUrlProvider
     */
    public function testAccessUnauthorizedForAnonymousUserOnSubDomain($method, $url)
    {
        if (mb_strpos($url, '{id}')) {
            $logbookEntry = LogbookEntryFactory::new()->notFinished()->withoutDamages()->create();
            $url = str_replace('{id}', $logbookEntry->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url, [], [],
            ['HTTP_HOST' => $client->getContainer()->getParameter('logbook_domain')]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @dataProvider onlyMainUrlProvider
     */
    public function testAccessForbiddenOnSubDomain($method, $url)
    {
        if (mb_strpos($url, '{id}')) {
            $logbookEntry = LogbookEntryFactory::new()->notFinished()->withoutDamages()->create();
            $url = str_replace('{id}', $logbookEntry->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request($method, $url, [], [],
            ['HTTP_HOST' => $client->getContainer()->getParameter('logbook_domain')]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @dataProvider mainAndLogbookSubdomainUrlProvider
     */
    public function testAccessOnSubDomain($method, $url)
    {
        if (mb_strpos($url, '{id}')) {
            $logbookEntry = LogbookEntryFactory::new()->notFinished()->withoutDamages()->create();
            $url = str_replace('{id}', $logbookEntry->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request($method, $url, [], [],
            ['HTTP_HOST' => $client->getContainer()->getParameter('logbook_domain')]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function mainAndLogbookSubdomainUrlProvider()
    {
        yield ['GET', '/logbook-entry'];
        yield ['GET', '/logbook-entry/new'];
        yield ['POST', '/logbook-entry/new'];
        yield ['GET', '/logbook-entry/{id}/finish'];
        yield ['POST', '/logbook-entry/{id}/finish'];
        yield ['GET', '/logbook-entry/statistics'];
    }

    public function onlyMainUrlProvider()
    {
        yield ['GET', '/logbook-entry/{id}/edit'];
        yield ['POST', '/logbook-entry/{id}/edit'];
        yield ['DELETE', '/logbook-entry/{id}'];
    }

    public function testIndexLogbookEntries()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry');

        $this->assertResponseIsSuccessful();
    }

    public function testNewLogbookEntry()
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C]);
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

        $logBookEntry = LogbookEntryFactory::repository()->findOneBy([], ['id' => 'DESC']);

        $this->assertSame($shell->getId(), $logBookEntry->getShell()->getId());
        $this->assertCount(2, $logBookEntry->getCrewMembers());
        $this->assertSame((new \DateTime())->format('d/m/Y'), $logBookEntry->getDate()->format('d/m/Y'));
        $this->assertSame('09:00', $logBookEntry->getStartAt()->format('H:i'));
        $this->assertNull($logBookEntry->getEndAt());
        $this->assertNull($logBookEntry->getCoveredDistance());
        $this->assertEmpty($logBookEntry->getShellDamages());
    }

    public function testNewLogbookEntryWithoutData()
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

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('label[for="logbook_entry_start_shell"] .form-error-message')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('label[for="logbook_entry_start_startAt"] .form-error-message')->text());
        $this->assertCount(2, $crawler->filter('.form-error-message'));

        LogbookEntryFactory::repository()->assertCount(0);
    }

    public function testNewLogbookEntryInvalidCrewSize()
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C]);
        $licences = LicenseFactory::new()->annualActive()->many(2)->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shell->getId(),
            'logbook_entry_start[crewMembers]' => [$licences[0]->getUser()->getId()],
            'logbook_entry_start[startAt]' => '9:00',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Le nombre de membre d\'équipage ne correspond pas au nombre de place.', $crawler->filter('label[for="logbook_entry_start_crewMembers"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        LogbookEntryFactory::repository()->assertCount(0);
    }

    public function testNewLogbookEntryWithCrewMemberOnWater()
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C]);
        $licences = LicenseFactory::new()->annualActive()->many(2)->create();
        LogbookEntryFactory::new()->notFinished()->create([
            'shell' => ShellFactory::new(['numberRowers' => 1, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C]),
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

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString(sprintf('Certains membres d\'équipage sont déjà sortis: %s.', $licences[0]->getUser()->getFullName()), $crawler->filter('label[for="logbook_entry_start_crewMembers"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        LogbookEntryFactory::repository()->assertCount(1);
    }

    public function testNewLogbookEntryWithInvalidRowerCategory()
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_A]);
        $licences = LicenseFactory::new(['user' => UserFactory::new(['rowerCategory' => User::ROWER_CATEGORY_C])->create()])
            ->annualActive()
            ->many(2)
            ->create()
        ;

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

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Certains membres d\'équipage ne sont pas autorisé sur ce bâteau:', $crawler->filter('label[for="logbook_entry_start_crewMembers"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        LogbookEntryFactory::repository()->assertCount(0);
    }

    public function testNewLogbookEntryWithInvalidLogbookEntryLimit()
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C]);
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

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Certains membres d\'équipage ont atteint leur limite de nombre de sorties:', $crawler->filter('label[for="logbook_entry_start_crewMembers"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        LogbookEntryFactory::repository()->assertCount(0);
    }

    public function testNewLogbookEntryWithShellOnWater()
    {
        $shell = ShellFactory::createOne(['numberRowers' => 1, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C]);
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

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Ce bâteau est déjà sorti.', $crawler->filter('label[for="logbook_entry_start_shell"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        LogbookEntryFactory::repository()->assertCount(1);
    }

    public function testNewLogbookEntryWithHighlyDamagedShell()
    {
        $damage = ShellDamageFactory::new()->highlyDamaged()->notRepaired()->create([
            'shell' => ShellFactory::createOne(['numberRowers' => 1, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C]),
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

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Ce bâteau est endommagé.', $crawler->filter('label[for="logbook_entry_start_shell"] .form-error-message')->text());
        $this->assertCount(1, $crawler->filter('.form-error-message'));

        LogbookEntryFactory::repository()->assertCount(0);
    }

    public function testNewLogbookEntryWithMediumDamagedShell()
    {
        $shell = ShellFactory::createOne(['numberRowers' => 1, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C]);
        $shellDamage = ShellDamageFactory::new()->mediumDamaged()->create(['shell' => $shell]);
        $license = LicenseFactory::new()->annualActive()->withValidLicense()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shellDamage->getShell()->getId(),
            'logbook_entry_start[crewMembers]' => [$license->getUser()->getId()],
            'logbook_entry_start[startAt]' => '9:00',
        ]);

        $this->assertResponseRedirects();

        LogbookEntryFactory::repository()->assertCount(1);
    }

    public function testUserListLogbookEntryFormAsAdmin()
    {
        LicenseFactory::new()->annualActive()->many(2)->create();
        LicenseFactory::new()->annualInactive()->many(3)->create();
        UserFactory::createMany(4);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $crawler = $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();
        $this->assertCount(10, $crawler->filter('#logbook_entry_start_crewMembers > option'));
    }

    public function testUserListLogbookEntryFormAsUser()
    {
        $users = LicenseFactory::new()->annualActive()->withValidLicense()->many(2)->create();
        LicenseFactory::new()->annualInactive()->many(3)->create();
        UserFactory::createMany(4);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($users[0]->getUser());
        $crawler = $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $crawler->filter('#logbook_entry_start_crewMembers > option'));
    }

    public function testNewLogbookEntryWithNonUserCrewMember()
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C]);
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

        $logBookEntry = LogbookEntryFactory::repository()->findOneBy([], ['id' => 'DESC']);

        $this->assertCount(1, $logBookEntry->getCrewMembers());
        $this->assertCount(1, $logBookEntry->getNonUserCrewMembers());
    }

    public function testNewLogbookEntryWithNonUserCrewMemberNoAvailableForUser()
    {
        $license = LicenseFactory::new()->annualActive()->withValidLicense()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($license->getUser());
        $crawler = $client->request('GET', '/logbook-entry/new');

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('#logbook_entry_start_nonUserCrewMembers'));
    }

    public function testNewLogbookEntryWithOnlyNonUserCrewMembers()
    {
        $shell = ShellFactory::new(['numberRowers' => 2, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_A])->create();

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

        $logBookEntry = LogbookEntryFactory::repository()->findOneBy([], ['id' => 'DESC']);

        $this->assertCount(0, $logBookEntry->getCrewMembers());
        $this->assertCount(2, $logBookEntry->getNonUserCrewMembers());
    }

    public function testEditLogbookEntry()
    {
        $shell = ShellFactory::new(['numberRowers' => 2, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C])->create();
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

        $entry->refresh();

        $this->assertSame($shell->getId(), $entry->getShell()->getId());
        $this->assertCount(2, $entry->getCrewMembers());
        $this->assertSame((new \DateTime())->format('d/m/Y'), $entry->getDate()->format('d/m/Y'));
        $this->assertSame('15:00', $entry->getStartAt()->format('H:i'));
        $this->assertSame('16:00', $entry->getEndAt()->format('H:i'));
        $this->assertSame(12.2, $entry->getCoveredDistance());
        $this->assertEmpty($entry->getShellDamages());
        $this->getEntityManager()->refresh($entry->getShell());
        $this->assertSame(12.2, $entry->getShell()->getMileage());
    }

    public function testEditLogbookEntryWithCrewMemberOnWater()
    {
        $license = LicenseFactory::new()->annualActive()->withValidLicense()->create();
        LogbookEntryFactory::new()->notFinished()->create([
            'shell' => ShellFactory::new(['numberRowers' => 1, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C])->create(),
            'crewMembers' => [$license->getUser()],
        ]);
        $entry = LogbookEntryFactory::new()->notFinished()->create([
            'shell' => ShellFactory::new(['numberRowers' => 1, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C])->create(),
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

        $entry->refresh();

        $this->assertCount(1, $entry->getCrewMembers());
        $this->assertSame($license->getUser()->getId(), $entry->getCrewMembers()->first()->getId());
        $this->assertCount(0, $entry->getNonUserCrewMembers());
    }

    public function testEditLogbookEntryWithShellOnWater()
    {
        $entries = LogbookEntryFactory::new()->notFinished()->many(2)->create([
            'shell' => ShellFactory::new(['numberRowers' => 1, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C])->create(),
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

        $entries[0]->refresh();

        $this->assertSame($entries[1]->getShell()->getId(), $entries[0]->getShell()->getId());
    }

    public function testEditLogbookEntryWithDamagedShell()
    {
        $shellDamage = ShellDamageFactory::new()->highlyDamaged()->create();
        $entry = LogbookEntryFactory::new()->notFinished()->create([
            'shell' => ShellFactory::new(['numberRowers' => 1, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C])->create(),
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

        $entry->refresh();

        $this->assertSame($shellDamage->getShell()->getId(), $entry->getShell()->getId());
    }

    public function testEditShellLogbookEntry()
    {
        $shell = ShellFactory::new(['numberRowers' => 2, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C])->create();
        $entryShell = ShellFactory::new(['numberRowers' => 2, 'coxed' => false, 'rowerCategory' => Shell::ROWER_CATEGORY_C])->create();
        $entry = LogbookEntryFactory::createOne([
            'shellDamages' => new ArrayCollection(),
            'shell' => $entryShell,
            'crewMembers' => UserFactory::new()->many($entryShell->getCrewSize()),
        ]);
        $previousShell = $entry->getShell();

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

        $entry->refresh();

        $this->assertSame($shell->getId(), $entry->getShell()->getId());
        $this->assertSame(10.0, $entry->getShell()->getMileage());

        $previousShell = ShellFactory::repository()->find($previousShell->getId());
        $previousShell->refresh();

        $this->assertSame(0.0, $previousShell->getMileage());
    }

    public function testFinishLogbookEntry()
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

        $entry->refresh();

        $this->assertSame('16:00', $entry->getEndAt()->format('H:i'));
        $this->assertSame(12.2, $entry->getCoveredDistance());
        $this->assertSame(12.2, $entry->getShell()->getMileage());
    }

    public function testFinishLogbookWithDamageEntry()
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

        $entry->refresh();

        $this->assertCount(2, $entry->getShellDamages());
        $this->assertSame($categories[0]->getId(), $entry->getShellDamages()->first()->getCategory()->getId());
        $this->assertNull($entry->getShellDamages()->first()->getDescription());
        $this->assertSame($categories[1]->getId(), $entry->getShellDamages()->last()->getCategory()->getId());
        $this->assertSame('A little description', $entry->getShellDamages()->last()->getDescription());
    }

    public function testDeleteLogbookEntry()
    {
        $entry = LogbookEntryFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_LOGBOOK_ADMIN');
        $client->request('GET', '/logbook-entry/'.$entry->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/logbook-entry');

        LogbookEntryFactory::repository()->assertNotExists(['id' => $entry->getId()]);

        $shell = ShellFactory::repository()->find($entry->getShell()->getId());

        $this->assertSame(0.0, $shell->getMileage());
    }
}
