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

use App\Factory\PhysicalQualitiesFactory;
use App\Factory\PhysiologyFactory;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Factory\WorkoutMaximumLoadFactory;
use App\Tests\AppWebTestCase;

use function Zenstruck\Foundry\faker;

class HomepageControllerTest extends AppWebTestCase
{
    public function testIndex(): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();

        $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithStats(): void
    {
        $user = UserFactory::createOne();
        PhysicalQualitiesFactory::createOne(['user' => $user]);
        PhysiologyFactory::createOne(['user' => $user]);
        WorkoutMaximumLoadFactory::createOne(['user' => $user]);
        TrainingFactory::createMany(20, [
            'user' => $user,
            'trainedAt' => faker()->dateTimeThisMonth(),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }
}
