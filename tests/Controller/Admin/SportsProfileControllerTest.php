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

namespace App\Tests\Controller\Admin;

use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class SportsProfileControllerTest extends AppWebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForAnonymousUser($method, $url)
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForRegularUser($method, $url)
    {
        if (mb_strpos($url, '{id}')) {
            $user = UserFactory::new()->create();
            $url = str_replace('{id}', $user->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_USER');
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider()
    {
        yield ['GET', '/admin/sports-profile'];
        yield ['GET', '/admin/sports-profile/{id}/physiology'];
    }

    public function testIndexUsers()
    {
        static::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SPORT_ADMIN');
        $client->request('GET', '/admin/sports-profile');

        $this->assertResponseIsSuccessful();
    }

    public function testNewPhysiology()
    {
        $user = UserFactory::new()->create();

        self::ensureKernelShutdown();
        $client = static::createClient();
        $this->logIn($client, 'ROLE_SPORT_ADMIN');
        $client->request('GET', '/admin/sports-profile/'.$user->getId().'/physiology');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'physiology[maximumHeartRate]' => 200,
            'physiology[maximumOxygenConsumption]' => 75.3,
            'physiology[lightAerobicHeartRate]' => 120,
            'physiology[heavyAerobicHeartRate]' => 150,
            'physiology[anaerobicThresholdHeartRate]' => 170,
            'physiology[oxygenTransportationHeartRate]' => 185,
            'physiology[anaerobicHeartRate]' => 200,
        ]);

        $this->assertResponseRedirects();

        $user->refresh();

        $this->assertNotNull($user->getPhysiology());
        $this->assertSame(200, $user->getPhysiology()->getMaximumHeartRate());
        $this->assertSame(75.3, $user->getPhysiology()->getMaximumOxygenConsumption());
        $this->assertSame(120, $user->getPhysiology()->getLightAerobicHeartRate());
        $this->assertSame(150, $user->getPhysiology()->getHeavyAerobicHeartRate());
        $this->assertSame(170, $user->getPhysiology()->getAnaerobicThresholdHeartRate());
        $this->assertSame(185, $user->getPhysiology()->getOxygenTransportationHeartRate());
        $this->assertSame(200, $user->getPhysiology()->getAnaerobicHeartRate());
    }
}
