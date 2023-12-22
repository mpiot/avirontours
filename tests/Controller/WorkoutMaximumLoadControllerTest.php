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

use App\Factory\LicenseFactory;
use App\Factory\WorkoutMaximumLoadFactory;
use App\Tests\AppWebTestCase;

class WorkoutMaximumLoadControllerTest extends AppWebTestCase
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

    public function urlProvider(): \Generator
    {
        yield ['GET', '/workout-maximum-load'];
    }

    public function testShowWorkoutMaximumLoad(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        WorkoutMaximumLoadFactory::createOne([
            'user' => $user,
        ]);

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/workout-maximum-load');

        $this->assertResponseIsSuccessful();
    }

    public function testShowWorkoutMaximumLoadWithoutInfos(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/workout-maximum-load');

        $this->assertResponseIsSuccessful();
    }
}
