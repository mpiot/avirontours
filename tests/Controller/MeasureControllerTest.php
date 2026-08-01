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

use App\Entity\Measure;
use App\Enum\MeasureType;
use App\Factory\LicenseFactory;
use App\Factory\MeasureFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

use function Zenstruck\Foundry\faker;

class MeasureControllerTest extends AppWebTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('urlProvider')]
    public function testAccessDeniedForAnonymousUser(string $method, string $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $measure = MeasureFactory::createOne();
            $url = str_replace('{id}', (string) $measure->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('urlProvider')]
    public function testAccessDeniedForUnlicensedUser(string $method, string $url): void
    {
        $user = UserFactory::createOne();

        if (mb_strpos($url, '{id}')) {
            $measure = MeasureFactory::createOne(['user' => $user]);
            $url = str_replace('{id}', (string) $measure->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testIndexMeasures(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        MeasureFactory::createMany(6, [
            'user' => $user,
            'measuredAt' => faker()->dateTimeThisMonth(),
        ]);
        MeasureFactory::createMany(3, [
            'measuredAt' => faker()->dateTimeThisMonth(),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);

        $crawler = $client->request('GET', '/measure');

        $this->assertResponseIsSuccessful();
        $this->assertCount(6, $crawler->filterXPath('//table/tbody/tr'));
    }

    public function testNewMeasure(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/measure/new');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'measure[measuredAt]' => '2020-01-15',
            'measure[type]' => MeasureType::RestingHeartRate->value,
            'measure[value]' => 49,
        ]);

        $this->assertResponseRedirects();

        /** @var Measure $measure */
        $measure = MeasureFactory::repository()->last();

        $this->assertSame('2020-01-15 00:00', $measure->getMeasuredAt()->format('Y-m-d H:i'));
        $this->assertSame(MeasureType::RestingHeartRate, $measure->getType());
        $this->assertSame(49.0, $measure->getValue());
    }

    public function testNewMeasureWithoutData(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/measure/new');
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'measure[measuredAt]' => '',
            'measure[type]' => '',
            'measure[value]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(3, $crawler->filter('.invalid-feedback'));
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#measure_measuredAt')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#measure_type')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#measure_value')->closest('.mb-3')->filter('.invalid-feedback')->text());

        MeasureFactory::repository()->assert()->count(0);
    }

    public function testNewMeasureAsDuplicated(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        MeasureFactory::createOne([
            'user' => $user,
            'measuredAt' => new \DateTimeImmutable('2020-01-15'),
            'type' => MeasureType::RestingHeartRate,
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/measure/new');
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'measure[measuredAt]' => '2020-01-15',
            'measure[type]' => MeasureType::RestingHeartRate->value,
            'measure[value]' => 49,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        $this->assertStringContainsString('Cette valeur est déjà utilisée.', $crawler->filter('#measure_measuredAt')->closest('.mb-3')->filter('.invalid-feedback')->text());

        MeasureFactory::repository()->assert()->count(1);
    }

    public function testEditMeasure(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $measure = MeasureFactory::createOne(['user' => $user]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/measure/'.$measure->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'measure[measuredAt]' => '2020-01-15',
            'measure[type]' => MeasureType::RestingHeartRate->value,
            'measure[value]' => 49,
        ]);

        $this->assertResponseRedirects();
        $this->assertSame('2020-01-15 00:00', $measure->getMeasuredAt()->format('Y-m-d H:i'));
        $this->assertSame(MeasureType::RestingHeartRate, $measure->getType());
        $this->assertSame(49.0, $measure->getValue());
    }

    public function testEditOtherUserMeasure(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $measure = MeasureFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/measure/'.$measure->getId().'/edit');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteMeasure(): void
    {
        $measure = MeasureFactory::createOne([
            'user' => $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser(),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/measure');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/measure');

        MeasureFactory::repository()->assert()->notExists($measure);
    }

    public static function urlProvider(): \Generator
    {
        yield ['GET', '/measure'];
        yield ['GET', '/measure/new'];
        yield ['POST', '/measure/new'];
        yield ['GET', '/measure/{id}/edit'];
        yield ['POST', '/measure/{id}/edit'];
        yield ['POST', '/measure/{id}'];
    }
}
