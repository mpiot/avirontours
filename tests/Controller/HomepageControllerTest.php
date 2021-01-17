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

use App\Factory\PhysicalQualitiesFactory;
use App\Factory\PhysiologyFactory;
use App\Factory\UserFactory;
use App\Factory\WorkoutMaximumLoadFactory;
use App\Tests\AppWebTestCase;

class HomepageControllerTest extends AppWebTestCase
{
    public function testIndex()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseRedirects('/login');

        $this->logIn($client, 'ROLE_USER');
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    public function testIndexWithStats()
    {
        $user = UserFactory::new()->create();
        PhysicalQualitiesFactory::new()->create(['user' => $user]);
        PhysiologyFactory::new()->create(['user' => $user]);
        WorkoutMaximumLoadFactory::new()->create(['user' => $user]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }
}
