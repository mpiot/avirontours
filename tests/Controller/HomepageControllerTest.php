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

use App\Enum\RatedPerceivedExertion;
use App\Factory\PhysiologyFactory;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Factory\WorkoutMaximumLoadFactory;
use App\Tests\AppWebTestCase;

use function Zenstruck\Foundry\faker;

class HomepageControllerTest extends AppWebTestCase
{
    public function testAsAnonymousUserICannotShowHomepage(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/login');
    }

    public function testIndex(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_USER');
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithStats(): void
    {
        $user = UserFactory::createOne();
        PhysiologyFactory::createOne(['user' => $user]);
        WorkoutMaximumLoadFactory::createOne(['user' => $user]);
        TrainingFactory::createMany(20, [
            'user' => $user,
            'trainedAt' => faker()->dateTimeThisMonth(),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString("Volume d'entraînement", $crawler->text());
        $this->assertStringNotContainsString('Mes sorties', $crawler->text());
        $this->assertStringNotContainsString('Répartition des sports', $crawler->text());
    }

    public function testDashboardShowsAcuteLoadConditionFatigueAndFreshness(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne([
            'user' => $user,
            'trainedAt' => new \DateTime('-1 day'),
            'duration' => 36000,
            'distance' => 10000,
            'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard,
        ]);
        TrainingFactory::createOne([
            'user' => $user,
            'trainedAt' => new \DateTime('-3 days'),
            'duration' => 54000,
            'distance' => 8000,
            'ratedPerceivedExertion' => null,
        ]);
        TrainingFactory::createOne([
            'user' => $user,
            'trainedAt' => new \DateTime('-20 days'),
            'duration' => 36000,
            'distance' => null,
            'ratedPerceivedExertion' => RatedPerceivedExertion::Easy,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', '7 derniers jours');
        // Rolling 7 days: 1 h + 1 h 30 and 10 km + 8 km; the -20 days session only feeds the chronic load
        $this->assertAnySelectorTextContains('dd', '02:30');
        $this->assertAnySelectorTextContains('dd', '18,0');
        $this->assertSelectorTextContains('body', "Charge d'entraînement");
        // Acute 240 (4 × 60 min); condition 51 and fatigue 211 from 120 twenty days ago and 240 yesterday
        $this->assertAnySelectorTextContains('dd', '240');
        $this->assertAnySelectorTextContains('dd', '51');
        $this->assertAnySelectorTextContains('dd', '211');
        $this->assertAnySelectorTextContains('dd', '-160');
        $this->assertSelectorTextContains('body', '1 séance notée sur 2');
    }

    public function testDashboardLoadCardInvitesRatingWhenNothingIsRated(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne([
            'user' => $user,
            'trainedAt' => new \DateTime('-1 day'),
            'ratedPerceivedExertion' => null,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', "Charge d'entraînement");
        $this->assertSelectorTextContains('body', "Notez l'effort de vos séances");
        $this->assertSelectorTextNotContains('body', 'Condition');
    }

    public function testDashboardLoadExplainsAnUnratedWeekInsteadOfHidingIt(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne([
            'user' => $user,
            'trainedAt' => new \DateTime('-1 day'),
            'ratedPerceivedExertion' => null,
        ]);
        TrainingFactory::createOne([
            'user' => $user,
            'trainedAt' => new \DateTime('-10 days'),
            'duration' => 36000,
            'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', "Charge d'entraînement");
        // Trained this week but nothing rated: the figures say why they are missing
        $this->assertAnySelectorTextContains('dd', 'aucune séance notée');
        $this->assertAnySelectorTextContains('dd', '31');
        $this->assertAnySelectorTextContains('dd', '51');
        $this->assertSelectorTextContains('body', "Notez l'effort de vos séances pour calculer les valeurs manquantes.");
    }

    public function testDashboardKpisStayVisibleDuringARestWeek(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne([
            'user' => $user,
            'trainedAt' => new \DateTime('-10 days'),
            'duration' => 36000,
            'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        // A week off is a real zero, not a missing feature
        $this->assertSelectorTextContains('body', '7 derniers jours');
        $this->assertAnySelectorTextContains('dd', '0');
        $this->assertSelectorTextContains('body', 'Fraîcheur');
    }

    public function testDashboardKpisHiddenForNewMember(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_USER');
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextNotContains('body', '7 derniers jours');
        $this->assertSelectorTextNotContains('body', "Charge d'entraînement");
    }

    public function testMySpace(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/my-space');

        $this->assertResponseRedirects('/login');

        $this->createAndLogin($client, 'ROLE_USER');
        $client->request('GET', '/my-space');

        $this->assertResponseIsSuccessful();
    }
}
