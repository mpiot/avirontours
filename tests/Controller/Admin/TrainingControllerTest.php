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

namespace App\Tests\Controller\Admin;

use App\Enum\Feeling;
use App\Factory\GroupFactory;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Response;

use function Zenstruck\Foundry\faker;

class TrainingControllerTest extends AppWebTestCase
{
    #[DataProvider('urlProvider')]
    public function testAccessDeniedForAnonymousUser(string $method, string $url): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    #[DataProvider('urlProvider')]
    public function testAccessDeniedForRegularUser(string $method, string $url): void
    {
        if (mb_strpos($url, '{user-id}')) {
            $user = UserFactory::createOne();
            $url = str_replace('{user-id}', (string) $user->getId(), $url);

            if (mb_strpos($url, '{id}')) {
                $training = TrainingFactory::createOne(['user' => $user]);
                $url = str_replace('{id}', (string) $training->getId(), $url);
            }
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testIndexTrainings(): void
    {
        TrainingFactory::createMany(20);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SPORT_ADMIN');
        $client->request('GET', '/admin/training');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexAveragesOnlyTheSessionsThatWereRated(): void
    {
        $ratedUser = UserFactory::createOne(['firstname' => 'A']);
        TrainingFactory::createOne(['user' => $ratedUser, 'feeling' => Feeling::VeryGood, 'trainedAt' => new \DateTime('yesterday')]);
        TrainingFactory::createOne(['user' => $ratedUser, 'feeling' => null, 'trainedAt' => new \DateTime('yesterday')]);

        $unratedUser = UserFactory::createOne(['firstname' => 'B']);
        TrainingFactory::createOne(['user' => $unratedUser, 'feeling' => null, 'trainedAt' => new \DateTime('yesterday')]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SPORT_ADMIN');
        $crawler = $client->request('GET', '/admin/training');

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $crawler->filterXPath('//td[@data-label="Sensation"]'));
        $this->assertStringContainsString("100\u{a0}%", $crawler->filterXPath('//td[@data-label="Sensation"][1]')->text());
        $this->assertStringContainsString('Aucune séance notée', $crawler->filterXPath('//td[@data-label="Sensation"][2]')->text());
    }

    public function testFilterIndexTrainings(): void
    {
        $user = UserFactory::createOne();
        $group = GroupFactory::createOne(['members' => [$user]]);
        TrainingFactory::createMany(20, ['user' => $user]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SPORT_ADMIN');
        $client->request('GET', \sprintf('/admin/training?group=%s', $group->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testListUserTrainings(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createMany(6, [
            'user' => $user,
            'trainedAt' => faker()->dateTimeThisMonth(),
        ]);
        TrainingFactory::createMany(3);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SPORT_ADMIN');
        $crawler = $client->request('GET', '/admin/training/'.$user->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', "Volume d'entraînement");
        $this->assertCount(6, $crawler->filter('.app-table > tbody > tr'));
    }

    public function testShowTraining(): void
    {
        $user = UserFactory::createOne();
        $training = TrainingFactory::createOne(['user' => $user]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SPORT_ADMIN');
        $client->request('GET', \sprintf('/admin/training/%s/%s', $user->getId(), $training->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testShowTrainingDoesNotOfferTheRatingForm(): void
    {
        $user = UserFactory::createOne();
        $training = TrainingFactory::createOne(['user' => $user, 'feeling' => null, 'ratedPerceivedExertion' => null]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->createAndLogin($client, 'ROLE_SPORT_ADMIN');
        $crawler = $client->request('GET', \sprintf('/admin/training/%s/%s', $user->getId(), $training->getId()));

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Non renseignée', $crawler->filterXPath('//div[@id="rating"]')->text());
        $this->assertCount(0, $crawler->filterXPath('//div[@id="rating"]/turbo-frame'));
        $this->assertCount(0, $crawler->filterXPath('//div[@id="rating"]/form'));
    }

    public static function urlProvider(): \Generator
    {
        yield ['GET', '/admin/training'];
        yield ['GET', '/admin/training/{user-id}'];
        yield ['GET', '/admin/training/{user-id}/{id}'];
    }
}
