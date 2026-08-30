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

namespace App\Tests\Chart;

use App\Chart\TrainingVolumeChart;
use App\Entity\User;
use App\Enum\SportType;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TrainingVolumeChartTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testEverySpecificityIsABandEvenWhenNothingWasPractised(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 36000, 'trainedAt' => new \DateTime('first day of this month')]);

        $bands = $this->bands($user);

        self::assertSame(['Spécifique', 'Semi-spécifique', 'Non-spécifique'], array_keys($bands));
        self::assertSame(1.0, array_last($bands['Spécifique']));
        self::assertSame(0.0, array_last($bands['Semi-spécifique']));
        self::assertSame(0.0, array_last($bands['Non-spécifique']));
    }

    public function testTheSportsOfOneSpecificityAddUpInHours(): void
    {
        $user = UserFactory::createOne();
        $thisMonth = new \DateTime('first day of this month');
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Cycling, 'duration' => 36000, 'trainedAt' => $thisMonth]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Running, 'duration' => 18000, 'trainedAt' => $thisMonth]);

        $bands = $this->bands($user);

        // 1 h of cycling and 30 min of running, in the same band.
        self::assertSame(1.5, array_last($bands['Non-spécifique']));
        self::assertSame(0.0, array_last($bands['Spécifique']));
    }

    public function testTwelveRollingMonthsEndingOnTheCurrentOne(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 36000, 'trainedAt' => new \DateTime('first day of this month')]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 18000, 'trainedAt' => new \DateTime('first day of this month -11 months')]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 72000, 'trainedAt' => new \DateTime('first day of this month -12 months')]);

        $band = $this->bands($user)['Spécifique'];

        self::assertCount(12, $band);
        self::assertSame(0.5, array_first($band));
        self::assertSame(1.0, array_last($band));
    }

    public function testNoChartWithoutASessionInTheLastTwelveMonths(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('first day of this month -12 months')]);

        self::assertNull(self::getContainer()->get(TrainingVolumeChart::class)->monthly($user));
    }

    /**
     * @return array<string, list<float>> the hours of each month, keyed by the band's label
     */
    private function bands(User $user): array
    {
        $datasets = self::getContainer()->get(TrainingVolumeChart::class)->monthly($user)->getData()['datasets'];

        return array_column($datasets, 'data', 'label');
    }
}
