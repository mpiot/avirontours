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

use App\Factory\GroupFactory;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;
use function Zenstruck\Foundry\faker;

class TrainingControllerTest extends AppWebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForAnonymousUser($method, $url): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForRegularUser($method, $url): void
    {
        if (mb_strpos($url, '{user_id}')) {
            $user = UserFactory::createOne();
            $url = str_replace('{id}', (string) $user->getId(), $url);

            if (mb_strpos($url, '{id}')) {
                $training = TrainingFactory::createOne(['user' => $user]);
                $url = str_replace('{id}', (string) $training->getId(), $url);
            }
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider()
    {
        yield ['GET', '/admin/training'];
        yield ['GET', '/admin/training/{user-id}'];
        yield ['GET', '/admin/training/{user-id}/{id}'];
    }

    public function testIndexTrainings(): void
    {
        TrainingFactory::createMany(20);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SPORT_ADMIN');
        $client->request('GET', '/admin/training');

        $this->assertResponseIsSuccessful();
    }

    public function testFilterIndexTrainings(): void
    {
        $user = UserFactory::createOne();
        $group = GroupFactory::createOne(['members' => [$user]]);
        TrainingFactory::createMany(20, ['user' => $user]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SPORT_ADMIN');
        $client->request('GET', sprintf('/admin/training?group=%s', $group->getId()));

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
        $this->logIn($client, 'ROLE_SPORT_ADMIN');
        $crawler = $client->request('GET', "/admin/training/{$user->getId()}");

        $this->assertResponseIsSuccessful();
        $this->assertCount(6, $crawler->filter('table > tbody > tr'));
    }

    public function testShowTraining(): void
    {
        $user = UserFactory::createOne();
        $training = TrainingFactory::createOne(['user' => $user]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SPORT_ADMIN');
        $client->request('GET', "/admin/training/{$user->getId()}/{$training->getId()}");

        $this->assertResponseIsSuccessful();
    }
}
