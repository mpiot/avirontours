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

use App\Entity\Training;
use App\Enum\SportType;
use App\Factory\LicenseFactory;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Tests\AppWebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TrainingControllerTest extends AppWebTestCase
{
    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForAnonymousUser($method, $url): void
    {
        if (mb_strpos($url, '{id}')) {
            $training = TrainingFactory::createOne();
            $url = str_replace('{id}', (string) $training->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->request($method, $url);

        $this->assertResponseRedirects('/login');
    }

    /**
     * @dataProvider urlProvider
     */
    public function testAccessDeniedForUnlicensedUser($method, $url): void
    {
        $user = UserFactory::createOne();

        if (mb_strpos($url, '{id}')) {
            $training = TrainingFactory::createOne(['user' => $user]);
            $url = str_replace('{id}', (string) $training->getId(), $url);
        }

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user->_real());
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider(): \Generator
    {
        yield ['GET', '/training'];
        yield ['GET', '/training/{id}'];
        yield ['GET', '/training/new'];
        yield ['POST', '/training/new'];
        yield ['GET', '/training/{id}/edit'];
        yield ['POST', '/training/{id}/edit'];
        yield ['POST', '/training/{id}'];
    }

    public function testIndexTrainings(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        TrainingFactory::createMany(6, [
            'trainedAt' => TrainingFactory::faker()->dateTimeThisMonth(),
            'user' => $user,
        ]);
        TrainingFactory::createMany(6, [
            'trainedAt' => new \DateTime('-2 months'),
            'user' => $user,
        ]);
        TrainingFactory::createMany(3, [
            'trainedAt' => TrainingFactory::faker()->dateTimeThisMonth(),
        ]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/training');

        $this->assertResponseIsSuccessful();
        $this->assertCount(6, $crawler->filterXPath('//div[@id="training-list"]//div[starts-with(@id, "training-")]'));
    }

    public function testShowTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne(['user' => $user]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/'.$training->getId());

        $this->assertResponseIsSuccessful();
    }

    public function testShowOtherUserTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/'.$training->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testNewTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'training[trainedAt]' => '2020-01-15',
            'training[sport]' => SportType::Rowing->value,
            'training[duration][hours]' => 1,
            'training[duration][minutes]' => 30,
            'training[distance]' => 16.3,
            'training[feeling]' => 0.75,
            'training[ratedPerceivedExertion]' => 4,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseRedirects();

        /** @var Training $training */
        $training = TrainingFactory::repository()->last();

        $this->assertSame('2020-01-15 00:00', $training->getTrainedAt()->format('Y-m-d H:i'));
        $this->assertSame(SportType::Rowing, $training->getSport());
        $this->assertSame(54000, $training->getDuration());
        $this->assertSame('01:30', $training->getFormattedDuration());
        $this->assertSame(16300, $training->getDistance());
        $this->assertSame(0.75, $training->getFeeling());
        $this->assertSame(4, $training->getRatedPerceivedExertion());
        $this->assertSame('My little comment...', $training->getComment());
    }

    public function testNewTrainingWithoutDistance(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $client->submitForm('Sauver', [
            'training[trainedAt]' => '2020-01-15',
            'training[sport]' => SportType::Rowing->value,
            'training[duration][hours]' => 1,
            'training[duration][minutes]' => 30,
            'training[distance]' => '',
            'training[feeling]' => 0.75,
            'training[ratedPerceivedExertion]' => 4,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseRedirects();

        /** @var Training $training */
        $training = TrainingFactory::repository()->last();

        $this->assertSame('2020-01-15 00:00', $training->getTrainedAt()->format('Y-m-d H:i'));
        $this->assertSame(SportType::Rowing, $training->getSport());
        $this->assertSame(54000, $training->getDuration());
        $this->assertSame('01:30', $training->getFormattedDuration());
        $this->assertNull($training->getDistance());
        $this->assertSame(0.75, $training->getFeeling());
        $this->assertSame(4, $training->getRatedPerceivedExertion());
        $this->assertSame('My little comment...', $training->getComment());
    }

    public function testNewTrainingWithTooLowDistance(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'training[trainedAt]' => '2020-01-15 14:02',
            'training[sport]' => SportType::Rowing->value,
            'training[duration][hours]' => 1,
            'training[duration][minutes]' => 30,
            'training[distance]' => 0,
            'training[feeling]' => 0.75,
            'training[ratedPerceivedExertion]' => 4,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur doit être supérieure à 0.', $crawler->filter('#training_distance')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testNewTrainingWithTooLongDistance(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'training[trainedAt]' => '2020-01-15 14:02',
            'training[sport]' => SportType::Rowing->value,
            'training[duration][hours]' => 1,
            'training[duration][minutes]' => 30,
            'training[distance]' => 501,
            'training[feeling]' => 0.75,
            'training[ratedPerceivedExertion]' => 4,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Un entraînement doit faire 400km maximum.', $crawler->filter('#training_distance')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testNewTrainingWithoutData(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/new');
        $this->assertResponseIsSuccessful();

        $crawler = $client->submitForm('Sauver', [
            'training[trainedAt]' => '',
            'training[sport]' => '',
            'training[duration][hours]' => '',
            'training[duration][minutes]' => '',
            'training[distance]' => '',
            'training[feeling]' => '',
            'training[ratedPerceivedExertion]' => 0,
            'training[comment]' => '',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#training_sport')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#training_trainedAt')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Un entraînement doit durer au moins 5 minutes.', $crawler->filter('#training_duration')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#training_feeling')->closest('.mb-3')->filter('.invalid-feedback')->text());
        $this->assertCount(0, $crawler->filter('.alert.alert-danger'));
        $this->assertCount(4, $crawler->filter('.invalid-feedback'));
        TrainingFactory::repository()->assert()->count(0);
    }

    public function testEditTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne(['user' => $user]);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/'.$training->getId().'/edit');

        $this->assertResponseIsSuccessful();

        $client->submitForm('Modifier', [
            'training[trainedAt]' => '2020-01-15',
            'training[sport]' => SportType::Rowing->value,
            'training[duration][hours]' => 1,
            'training[duration][minutes]' => 30,
            'training[distance]' => 16.3,
            'training[feeling]' => 0.75,
            'training[ratedPerceivedExertion]' => 4,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseRedirects();
        $this->assertSame('2020-01-15 00:00', $training->getTrainedAt()->format('Y-m-d H:i'));
        $this->assertSame(SportType::Rowing, $training->getSport());
        $this->assertSame(54000, $training->getDuration());
        $this->assertSame('01:30', $training->getFormattedDuration());
        $this->assertSame(16300, $training->getDistance());
        $this->assertSame(0.75, $training->getFeeling());
        $this->assertSame(4, $training->getRatedPerceivedExertion());
        $this->assertSame('My little comment...', $training->getComment());
    }

    public function testEditOtherUserTraining(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        $training = TrainingFactory::createOne();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/'.$training->getId().'/edit');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteTraining(): void
    {
        $training = TrainingFactory::createOne([
            'user' => $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser(),
        ])->_disableAutoRefresh();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $client->request('GET', '/training/'.$training->getId());

        $this->assertResponseIsSuccessful();

        $client->submitForm('Supprimer');

        $this->assertResponseRedirects('/training');

        TrainingFactory::repository()->assert()->notExists($training);
    }
}
