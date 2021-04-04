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
use App\Entity\TrainingPhase;
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
        $client->loginUser($user->object());
        $client->request($method, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function urlProvider()
    {
        yield ['GET', '/training'];
        yield ['GET', '/training/{id}'];
        yield ['GET', '/training/new'];
        yield ['POST', '/training/new'];
        yield ['GET', '/training/{id}/edit'];
        yield ['POST', '/training/{id}/edit'];
        yield ['DELETE', '/training/{id}'];
    }

    public function testIndexTrainings(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();
        TrainingFactory::createMany(6, ['user' => $user]);
        TrainingFactory::createMany(3);

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/training');

        $this->assertResponseIsSuccessful();
        $this->assertCount(6, $crawler->filter('table > tbody > tr'));
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
            'training[trained_at][date]' => '2020-01-15',
            'training[trained_at][time]' => '14:02',
            'training[sport]' => Training::SPORT_ROWING,
            'training[duration][hours]' => 1,
            'training[duration][minutes]' => 30,
            'training[distance]' => 16.3,
            'training[feeling]' => Training::FEELING_OK,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseRedirects();

        /** @var Training $training */
        $training = TrainingFactory::repository()->last();

        $this->assertSame('2020-01-15 14:02', $training->getTrainedAt()->format('Y-m-d H:i'));
        $this->assertSame(Training::SPORT_ROWING, $training->getSport());
        $this->assertSame('01:30', $training->getDuration()->format('%H:%I'));
        $this->assertSame(16.3, $training->getDistance());
        $this->assertSame(Training::FEELING_OK, $training->getFeeling());
        $this->assertSame('My little comment...', $training->getComment());
    }

    public function testNewTrainingWithPhases(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/training/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauver')->form([
            'training[trained_at][date]' => '2020-01-15',
            'training[trained_at][time]' => '14:02',
            'training[sport]' => Training::SPORT_ROWING,
            'training[duration][hours]' => 1,
            'training[duration][minutes]' => 30,
            'training[distance]' => 16.3,
            'training[feeling]' => Training::FEELING_OK,
            'training[comment]' => 'My little comment...',
        ]);
        $values = $form->getPhpValues();
        $values['training']['trainingPhases'][0]['name'] = 'Phase name';
        $values['training']['trainingPhases'][0]['intensity'] = TrainingPhase::INTENSITY_ANAEROBIC_THRESHOLD;
        $values['training']['trainingPhases'][0]['duration']['hours'] = 0;
        $values['training']['trainingPhases'][0]['duration']['minutes'] = 2;
        $values['training']['trainingPhases'][0]['duration']['seconds'] = 15;
        $values['training']['trainingPhases'][0]['distance'] = 2;
        $values['training']['trainingPhases'][0]['split'] = '1:48.6';
        $values['training']['trainingPhases'][0]['spm'] = 10;
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseRedirects();

        /** @var Training $training */
        $training = TrainingFactory::repository()->last();

        $this->assertSame('2020-01-15 14:02', $training->getTrainedAt()->format('Y-m-d H:i'));
        $this->assertSame(Training::SPORT_ROWING, $training->getSport());
        $this->assertSame('01:30', $training->getDuration()->format('%H:%I'));
        $this->assertSame(16.3, $training->getDistance());
        $this->assertSame(Training::FEELING_OK, $training->getFeeling());
        $this->assertSame('My little comment...', $training->getComment());
        $this->assertCount(1, $training->getTrainingPhases());
        $this->assertSame('Phase name', $training->getTrainingPhases()->first()->getName());
        $this->assertSame(TrainingPhase::INTENSITY_ANAEROBIC_THRESHOLD, $training->getTrainingPhases()->first()->getIntensity());
        $this->assertSame('00:02:15', $training->getTrainingPhases()->first()->getDuration()->format('%H:%I:%S'));
        $this->assertSame(2.0, $training->getTrainingPhases()->first()->getDistance());
        $this->assertSame('1:48.6', $training->getTrainingPhases()->first()->getSplit());
        $this->assertSame(10, $training->getTrainingPhases()->first()->getSpm());
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
            'training[trained_at][date]' => '',
            'training[trained_at][time]' => '',
            'training[sport]' => '',
            'training[duration][hours]' => 0,
            'training[duration][minutes]' => 0,
            'training[distance]' => '',
            'training[feeling]' => '',
            'training[comment]' => '',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#training_sport')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('.invalid-feedback')->eq(1)->text());
        $this->assertStringContainsString('Un entraînement doit durer au moins 5 minutes.', $crawler->filter('.invalid-feedback')->eq(2)->text());
        $this->assertCount(3, $crawler->filter('.invalid-feedback'));

        TrainingFactory::repository()->assertCount(0);
    }

    public function testNewTrainingWithEmptyPhase(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/training/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauver')->form([
            'training[trained_at][date]' => '2020-01-15',
            'training[trained_at][time]' => '14:02',
            'training[sport]' => Training::SPORT_ROWING,
            'training[duration][hours]' => 1,
            'training[duration][minutes]' => 30,
            'training[distance]' => 16.3,
            'training[feeling]' => Training::FEELING_OK,
            'training[comment]' => 'My little comment...',
        ]);
        $values = $form->getPhpValues();
        $values['training']['trainingPhases'][0]['name'] = '';
        $values['training']['trainingPhases'][0]['intensity'] = '';
        $values['training']['trainingPhases'][0]['duration']['hours'] = '';
        $values['training']['trainingPhases'][0]['duration']['minutes'] = '';
        $values['training']['trainingPhases'][0]['duration']['seconds'] = '';
        $values['training']['trainingPhases'][0]['distance'] = '';
        $values['training']['trainingPhases'][0]['split'] = '';
        $values['training']['trainingPhases'][0]['spm'] = '';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('#training_trainingPhases_0_intensity')->parents()->filter('.invalid-feedback')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être nulle.', $crawler->filter('.invalid-feedback')->eq(1)->text());
        $this->assertCount(2, $crawler->filter('.invalid-feedback'));

        TrainingFactory::repository()->assertCount(0);
    }

    public function testNewTrainingWithBadSplitInPhase(): void
    {
        $user = LicenseFactory::new()->annualActive()->withValidLicense()->create()->getUser();

        static::ensureKernelShutdown();
        $client = static::createClient();
        $client->loginUser($user);
        $crawler = $client->request('GET', '/training/new');

        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Sauver')->form([
            'training[trained_at][date]' => '2020-01-15',
            'training[trained_at][time]' => '14:02',
            'training[sport]' => Training::SPORT_ROWING,
            'training[duration][hours]' => 1,
            'training[duration][minutes]' => 30,
            'training[distance]' => 16.3,
            'training[feeling]' => Training::FEELING_OK,
            'training[comment]' => 'My little comment...',
        ]);
        $values = $form->getPhpValues();
        $values['training']['trainingPhases'][0]['name'] = '';
        $values['training']['trainingPhases'][0]['intensity'] = TrainingPhase::INTENSITY_ANAEROBIC_THRESHOLD;
        $values['training']['trainingPhases'][0]['duration']['hours'] = 0;
        $values['training']['trainingPhases'][0]['duration']['minutes'] = 2;
        $values['training']['trainingPhases'][0]['duration']['seconds'] = 15;
        $values['training']['trainingPhases'][0]['distance'] = '';
        $values['training']['trainingPhases'][0]['split'] = '2';
        $values['training']['trainingPhases'][0]['spm'] = '';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Le split doit avoir le format: "0:00.0".', $crawler->filter('#training_trainingPhases_0_split')->parents()->filter('.invalid-feedback')->text());
        $this->assertCount(1, $crawler->filter('.invalid-feedback'));

        TrainingFactory::repository()->assertCount(0);
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
            'training[trained_at][date]' => '2020-01-15',
            'training[trained_at][time]' => '14:02',
            'training[sport]' => Training::SPORT_ROWING,
            'training[duration][hours]' => 1,
            'training[duration][minutes]' => 30,
            'training[distance]' => 16.3,
            'training[feeling]' => Training::FEELING_OK,
            'training[comment]' => 'My little comment...',
        ]);

        $this->assertResponseRedirects();
        $this->assertSame('2020-01-15 14:02', $training->getTrainedAt()->format('Y-m-d H:i'));
        $this->assertSame(Training::SPORT_ROWING, $training->getSport());
        $this->assertSame('01:30', $training->getDuration()->format('%H:%I'));
        $this->assertSame(16.3, $training->getDistance());
        $this->assertSame(Training::FEELING_OK, $training->getFeeling());
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
        ])->disableAutoRefresh();

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
