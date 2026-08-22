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

use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;

class SportProfileControllerTest extends AppWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('urlProvider')]
    public function testAccessDeniedForAnonymousUser(string $method, string $url): void
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    public function testNewPhysiology(): void
    {
        $user = UserFactory::createOne();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/sport-profile/physiology');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'physiology[maximumOxygenConsumption]' => 75.3,
            'physiology[lightAerobicHeartRateMin]' => 120,
            'physiology[heavyAerobicHeartRateMin]' => 150,
            'physiology[anaerobicThresholdHeartRateMin]' => 170,
            'physiology[oxygenTransportationHeartRateMin]' => 185,
            'physiology[anaerobicHeartRateMin]' => 200,
            'physiology[maximumHeartRate]' => 215,
        ]);

        $this->assertResponseRedirects();
        $this->assertNotNull($user->getPhysiology());
        $this->assertSame(75.3, $user->getPhysiology()->getMaximumOxygenConsumption());
        $this->assertSame(120, $user->getPhysiology()->getLightAerobicHeartRateMin());
        $this->assertSame(150, $user->getPhysiology()->getHeavyAerobicHeartRateMin());
        $this->assertSame(170, $user->getPhysiology()->getAnaerobicThresholdHeartRateMin());
        $this->assertSame(185, $user->getPhysiology()->getOxygenTransportationHeartRateMin());
        $this->assertSame(200, $user->getPhysiology()->getAnaerobicHeartRateMin());
        $this->assertSame(215, $user->getPhysiology()->getMaximumHeartRate());
    }

    public function testNewWorkoutMaximumLoad(): void
    {
        $user = UserFactory::createOne();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/sport-profile/workout-maximum-load');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'workout_maximum_load[rowingTirage]' => 1,
            'workout_maximum_load[benchPress]' => 2,
            'workout_maximum_load[squat]' => 3,
            'workout_maximum_load[legPress]' => 4,
            'workout_maximum_load[clean]' => 5,
        ]);

        $this->assertResponseRedirects();
        $this->assertNotNull($user->getWorkoutMaximumLoad());
        $this->assertSame(1, $user->getWorkoutMaximumLoad()->getRowingTirage());
        $this->assertSame(2, $user->getWorkoutMaximumLoad()->getBenchPress());
        $this->assertSame(3, $user->getWorkoutMaximumLoad()->getSquat());
        $this->assertSame(4, $user->getWorkoutMaximumLoad()->getLegPress());
        $this->assertSame(5, $user->getWorkoutMaximumLoad()->getClean());
    }

    public function testConfiguration(): void
    {
        $user = UserFactory::createOne();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/sport-profile/configuration');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Enregistrer', [
            'sport_profile_confiruration[automaticTraining]' => 1,
        ]);

        $this->assertResponseRedirects();
        $this->assertTrue($user->getAutomaticTraining());
    }

    public static function urlProvider(): \Generator
    {
        yield ['GET', '/sport-profile/physiology'];
        yield ['GET', '/sport-profile/workout-maximum-load'];
        yield ['GET', '/sport-profile/configuration'];
    }
}
