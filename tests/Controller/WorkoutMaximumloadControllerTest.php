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

class WorkoutMaximumloadControllerTest extends AppWebTestCase
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

    public function urlProvider()
    {
        yield ['GET', '/workout-maximum-load'];
        yield ['GET', '/workout-maximum-load/edit'];
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

    public function testEditWorkoutMaximumLoad(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $workoutMaximumLoad = WorkoutMaximumLoadFactory::createOne([
            'user' => $user,
        ]);

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/workout-maximum-load/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'workout_maximum_load[rowingTirage]' => 1,
            'workout_maximum_load[benchPress]' => 2,
            'workout_maximum_load[squat]' => 3,
            'workout_maximum_load[legPress]' => 4,
            'workout_maximum_load[clean]' => 5,
        ]);

        $this->assertResponseRedirects();
        $this->assertSame(1, $workoutMaximumLoad->getRowingTirage());
        $this->assertSame(2, $workoutMaximumLoad->getBenchPress());
        $this->assertSame(3, $workoutMaximumLoad->getSquat());
        $this->assertSame(4, $workoutMaximumLoad->getLegPress());
        $this->assertSame(5, $workoutMaximumLoad->getClean());
    }
}
