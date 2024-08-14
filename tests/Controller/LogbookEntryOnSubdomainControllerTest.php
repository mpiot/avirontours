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

use App\Enum\SportType;
use App\Enum\TrainingType;
use App\Factory\LicenseFactory;
use App\Factory\LogbookEntryFactory;
use App\Factory\ShellDamageCategoryFactory;
use App\Factory\ShellDamageFactory;
use App\Factory\ShellFactory;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class LogbookEntryOnSubdomainControllerTest extends AppWebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testAccessUnauthorizedForAnonymousUser($method, $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $logbookEntry = LogbookEntryFactory::new()->notFinished()->withoutDamages()->create();
            $url = str_replace('{id}', (string) $logbookEntry->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url, server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @dataProvider urlProvider
     */
    public function testAccessForbidden($method, $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $logbookEntry = LogbookEntryFactory::new()->notFinished()->withoutDamages()->create();
            $url = str_replace('{id}', (string) $logbookEntry->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request($method, $url, server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider(): \Generator
    {
        yield ['GET', '/logbook-entry/{id}/edit'];
        yield ['POST', '/logbook-entry/{id}/edit'];
        yield ['POST', '/logbook-entry/{id}'];
    }

    public function testIndexLogbookEntries(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

        $this->assertResponseIsSuccessful();
    }

    public function testNewLogbookEntry(): void
    {
        $shell = ShellFactory::createOne(['numberRowers' => 2, 'coxed' => false]);
        $licences = LicenseFactory::new()->annualActive()->withValidLicense()->many(2)->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $crawler = $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'logbook_entry_start[shell]' => $shellDamage->getShell()->getId(),
            'logbook_entry_start[crewMembers]' => [$license->getUser()->getId()],
            'logbook_entry_start[startAt]' => '9:00',
        ]);

        $this->assertResponseRedirects();
        LogbookEntryFactory::repository()->assert()->count(1);
    }

    public function testNewLogbookEntryWithNonUserCrewMemberNoAvailableForUser(): void
    {
        LicenseFactory::new()->annualActive()->withValidLicense()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $crawler = $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('#logbook_entry_start_nonUserCrewMembers'));
    }

    public function testUserListLogbookEntryForm(): void
    {
        LicenseFactory::new()->annualActive()->withValidLicense()->many(1)->create();
        LicenseFactory::new()->annualActive()->withInvalidLicense()->many(2)->create();
        LicenseFactory::new()->annualInactive()->many(4)->create();
        UserFactory::createMany(8);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $crawler = $client->request('GET', '/logbook-entry/new', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $crawler->filter('#logbook_entry_start_crewMembers > option'));
    }

    public function testFinishLogbookEntry(): void
    {
        $entry = LogbookEntryFactory::new()->notFinished()->create();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry/'.$entry->getId().'/finish', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $client->request('GET', '/logbook-entry/'.$entry->getId().'/finish', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $training = TrainingFactory::repository()->first();
        $this->assertSame($entry->getCrewMembers()->first(), $training->getUser());
        $this->assertSame('2022-01-15 14:30:00', $training->getTrainedAt()->format('Y-m-d H:i:s'));
        $this->assertSame(12200, $training->getDistance());
        $this->assertSame(54000, $training->getDuration());
        $this->assertSame(SportType::Rowing, $training->getSport());
        $this->assertSame(TrainingType::B1, $training->getType());
        $training = TrainingFactory::repository()->last();
        $this->assertSame($entry->getCrewMembers()->last(), $training->getUser());
        $this->assertSame('2022-01-15 14:30:00', $training->getTrainedAt()->format('Y-m-d H:i:s'));
        $this->assertSame(12200, $training->getDistance());
        $this->assertSame(54000, $training->getDuration());
        $this->assertSame(SportType::Rowing, $training->getSport());
        $this->assertSame(TrainingType::B1, $training->getType());
    }

    public function testFinishLogbookWithDamageEntry(): void
    {
        $entry = LogbookEntryFactory::new()->notFinished()->withoutDamages()->create();
        $categories = ShellDamageCategoryFactory::createMany(2);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->setServerParameters([
            'PHP_AUTH_USER' => 'logbook',
            'PHP_AUTH_PW' => 'engage',
        ]);
        $crawler = $client->request('GET', '/logbook-entry/'.$entry->getId().'/finish', server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

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
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles(), server: ['HTTP_HOST' => 'cahierdesorties.avirontours.wip']);

        $this->assertResponseRedirects();
        $this->assertCount(2, $entry->getShellDamages());
        $this->assertSame($categories[0]->getId(), $entry->getShellDamages()->first()->getCategory()->getId());
        $this->assertNull($entry->getShellDamages()->first()->getDescription());
        $this->assertSame($categories[1]->getId(), $entry->getShellDamages()->last()->getCategory()->getId());
        $this->assertSame('A little description', $entry->getShellDamages()->last()->getDescription());
    }
}
