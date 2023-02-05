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
        yield ['GET', '/sport-profile/physiology'];
        yield ['GET', '/sport-profile/anatomy'];
        yield ['GET', '/sport-profile/physical-qualities'];
        yield ['GET', '/sport-profile/workout-maximum-load'];
        yield ['GET', '/sport-profile/configuration'];
    }

    public function testNewPhysiology(): void
    {
        $user = UserFactory::createOne();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/sport-profile/physiology');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
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

    public function testNewAnatomy(): void
    {
        $user = UserFactory::createOne();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/sport-profile/anatomy');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'anatomy[height]' => 175,
            'anatomy[weight]' => 69.1,
            'anatomy[armSpan]' => 160,
            'anatomy[bustLength]' => 75,
            'anatomy[legLength]' => 100,
        ]);

        $this->assertResponseRedirects();
        $this->assertNotNull($user->getAnatomy());
        $this->assertSame(175, $user->getAnatomy()->getHeight());
        $this->assertSame(69.1, $user->getAnatomy()->getWeight());
        $this->assertSame(160, $user->getAnatomy()->getArmSpan());
        $this->assertSame(75, $user->getAnatomy()->getBustLength());
        $this->assertSame(100, $user->getAnatomy()->getLegLength());
    }

    public function testNewPhysicalQualities(): void
    {
        $user = UserFactory::createOne();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/sport-profile/physical-qualities');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'physical_qualities[proprioception]' => 1,
            'physical_qualities[weightPowerRatio]' => 2,
            'physical_qualities[explosiveStrength]' => 3,
            'physical_qualities[enduranceStrength]' => 4,
            'physical_qualities[maximumStrength]' => 5,
            'physical_qualities[stressResistance]' => 6,
            'physical_qualities[coreStrength]' => 7,
            'physical_qualities[flexibility]' => 8,
            'physical_qualities[recovery]' => 9,
        ]);

        $this->assertResponseRedirects();
        $this->assertNotNull($user->getPhysicalQualities());
        $this->assertSame(1, $user->getPhysicalQualities()->getProprioception());
        $this->assertSame(2, $user->getPhysicalQualities()->getWeightPowerRatio());
        $this->assertSame(3, $user->getPhysicalQualities()->getExplosiveStrength());
        $this->assertSame(4, $user->getPhysicalQualities()->getEnduranceStrength());
        $this->assertSame(5, $user->getPhysicalQualities()->getMaximumStrength());
        $this->assertSame(6, $user->getPhysicalQualities()->getStressResistance());
        $this->assertSame(7, $user->getPhysicalQualities()->getCoreStrength());
        $this->assertSame(8, $user->getPhysicalQualities()->getFlexibility());
        $this->assertSame(9, $user->getPhysicalQualities()->getRecovery());
    }

    public function testNewWorkoutMaximumLoad(): void
    {
        $user = UserFactory::createOne();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user->object());
        $client->request('GET', '/sport-profile/workout-maximum-load');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
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
        $client->loginUser($user->object());
        $client->request('GET', '/sport-profile/configuration');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'sport_profile_confiruration[automaticTraining]' => 1,
        ]);

        $this->assertResponseRedirects();
        $this->assertTrue($user->getAutomaticTraining());
    }
}
