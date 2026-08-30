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

use App\Chart\MeasureCharts;
use App\Entity\User;
use App\Enum\MeasureType;
use App\Factory\MeasureFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * A chart's datasets are daily series ending today, so a slot is addressed as "N days ago" throughout.
 */
class MeasureChartsTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testNoChartAtAllWithoutMeasure(): void
    {
        self::assertSame([], self::getContainer()->get(MeasureCharts::class)->charts(UserFactory::createOne()));
    }

    public function testATypeTheMemberNeverMeasuredHasNoChart(): void
    {
        $user = UserFactory::createOne();
        MeasureFactory::createOne(['user' => $user, 'type' => MeasureType::Weight, 'measuredAt' => new \DateTimeImmutable('today'), 'value' => 74.0]);

        $charts = self::getContainer()->get(MeasureCharts::class)->charts($user);

        self::assertSame(['Poids'], array_column($charts, 'title'));
    }

    public function testAnotherMemberMeasuresDoNotMakeAChart(): void
    {
        $user = UserFactory::createOne();
        MeasureFactory::createOne(['type' => MeasureType::RestingHeartRate, 'measuredAt' => new \DateTimeImmutable('today'), 'value' => 50.0]);

        self::assertSame([], self::getContainer()->get(MeasureCharts::class)->charts($user));
    }

    public function testTheAxisRunsDailyFromOneYearAgoToToday(): void
    {
        $user = UserFactory::createOne();
        MeasureFactory::createOne(['user' => $user, 'type' => MeasureType::Weight, 'measuredAt' => new \DateTimeImmutable('today'), 'value' => 74.0]);
        MeasureFactory::createOne(['user' => $user, 'type' => MeasureType::Weight, 'measuredAt' => new \DateTimeImmutable('today -1 year'), 'value' => 70.0]);
        MeasureFactory::createOne(['user' => $user, 'type' => MeasureType::Weight, 'measuredAt' => new \DateTimeImmutable('today -1 year -1 day'), 'value' => 60.0]);

        $measures = $this->series($user)['Mesures'];
        $expectedDays = new \DateTimeImmutable('today')->diff(new \DateTimeImmutable('today -1 year'))->days + 1;

        self::assertCount($expectedDays, $measures);
        self::assertSame(70.0, array_first($measures));
        self::assertSame(74.0, array_last($measures));
    }

    public function testADayWithoutMeasureKeepsItsSlotAsNull(): void
    {
        $user = UserFactory::createOne();
        MeasureFactory::createOne(['user' => $user, 'type' => MeasureType::Weight, 'measuredAt' => new \DateTimeImmutable('today -1 day'), 'value' => 74.0]);

        $measures = $this->series($user)['Mesures'];

        self::assertSame(74.0, self::daysAgo($measures, 1));
        self::assertNull(self::daysAgo($measures, 0));
    }

    public function testTrendIsTheMeanOfTheMeasuresOfTheLastSevenDays(): void
    {
        $user = UserFactory::createOne();
        MeasureFactory::createOne(['user' => $user, 'type' => MeasureType::Weight, 'measuredAt' => new \DateTimeImmutable('today -10 days'), 'value' => 70.0]);
        MeasureFactory::createOne(['user' => $user, 'type' => MeasureType::Weight, 'measuredAt' => new \DateTimeImmutable('today -8 days'), 'value' => 74.0]);

        $trend = $this->series($user)['Tendance 7 jours'];

        self::assertSame(70.0, self::daysAgo($trend, 10));
        self::assertSame(72.0, self::daysAgo($trend, 8));
    }

    public function testTrendBreaksOnceSevenDaysPassedWithoutMeasure(): void
    {
        $user = UserFactory::createOne();
        MeasureFactory::createOne(['user' => $user, 'type' => MeasureType::Weight, 'measuredAt' => new \DateTimeImmutable('today -10 days'), 'value' => 74.0]);

        $trend = $this->series($user)['Tendance 7 jours'];

        self::assertSame(74.0, self::daysAgo($trend, 4));
        self::assertNull(self::daysAgo($trend, 3));
    }

    /**
     * @return array<string, list<float|null>> the daily values of the member's only chart, keyed by dataset label
     */
    private function series(User $user): array
    {
        $charts = self::getContainer()->get(MeasureCharts::class)->charts($user);
        self::assertCount(1, $charts);

        return array_column($charts[0]['chart']->getData()['datasets'], 'data', 'label');
    }

    /**
     * @param list<float|null> $daily
     */
    private static function daysAgo(array $daily, int $days): ?float
    {
        return $daily[\count($daily) - 1 - $days];
    }
}
