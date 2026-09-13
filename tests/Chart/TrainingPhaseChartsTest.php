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

use App\Chart\TrainingPhaseCharts;
use App\Entity\TrainingPhase;
use App\Enum\SportType;
use App\Factory\TrainingFactory;
use App\Factory\TrainingPhaseFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TrainingPhaseChartsTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testEachSeriesGetsItsOwnChart(): void
    {
        $phase = $this->phase(SportType::Ergometer, [
            'times' => [10, 20],
            'paces' => [1200, 1250],
            'strokeRates' => [24, 25],
            'heartRates' => [140, 150],
            'powers' => [200, 210],
        ]);

        $charts = $this->charts($phase);

        self::assertSame(
            ['Allure' => 'pace', 'Cadence' => 'spm', 'Fréquence cardiaque' => 'bpm', 'Puissance' => 'W'],
            array_combine(array_column($charts, 'title'), array_column($charts, 'unit')),
        );
    }

    public function testAHeartRateOnlyPhaseChartsTheHeartRateAlone(): void
    {
        $phase = $this->phase(SportType::Cycling, [
            'times' => [10, 20],
            'paces' => null,
            'strokeRates' => null,
            'heartRates' => [90, 95],
            'distances' => null,
        ]);

        $charts = $this->charts($phase);

        self::assertSame(['Fréquence cardiaque'], array_column($charts, 'title'));
    }

    public function testWithoutTimesThereIsNothingToDraw(): void
    {
        $phase = TrainingPhaseFactory::new()->withoutSeries()->create();

        self::assertSame([], $this->charts($phase));
    }

    public function testThePaceChartSpeaksTheUnitOfTheSport(): void
    {
        // 1250 tenths of a second per 500 m: 125 s /500m, 250 s /km, 25 s /100m, 14.4 km/h.
        $series = ['times' => [10, 20], 'paces' => [1250, 1250], 'strokeRates' => null, 'heartRates' => null, 'distances' => null];

        $rowing = $this->charts($this->phase(SportType::Rowing, $series))[0];
        self::assertSame('pace', $rowing['unit']);
        self::assertSame([125, 125], $rowing['chart']->getData()['datasets'][0]['data']);

        $running = $this->charts($this->phase(SportType::Running, $series))[0];
        self::assertSame('pace', $running['unit']);
        self::assertSame([250, 250], $running['chart']->getData()['datasets'][0]['data']);

        $swimming = $this->charts($this->phase(SportType::Swimming, $series))[0];
        self::assertSame([25, 25], $swimming['chart']->getData()['datasets'][0]['data']);

        $cycling = $this->charts($this->phase(SportType::Cycling, $series))[0];
        self::assertSame('Vitesse', $cycling['title']);
        self::assertSame('speed', $cycling['unit']);
        self::assertSame([14.4, 14.4], $cycling['chart']->getData()['datasets'][0]['data']);
    }

    public function testASportWithoutASpeedUnitHasNoPaceChart(): void
    {
        $phase = $this->phase(SportType::WeightTraining, [
            'times' => [10, 20],
            'paces' => [1250, 1250],
            'strokeRates' => null,
            'heartRates' => [120, 121],
            'distances' => null,
        ]);

        self::assertSame(['Fréquence cardiaque'], array_column($this->charts($phase), 'title'));
    }

    public function testLongSeriesAreAveragedDownToSixHundredPoints(): void
    {
        $count = 7200;
        $phase = $this->phase(SportType::Ergometer, [
            'times' => range(0, ($count - 1) * 10, 10),
            'paces' => array_fill(0, $count, 1200),
            'strokeRates' => array_fill(0, $count, 24),
            'heartRates' => null,
            'distances' => null,
        ]);

        $charts = $this->charts($phase);

        self::assertCount(600, $charts[0]['chart']->getData()['labels']);
        self::assertCount(600, $charts[0]['chart']->getData()['datasets'][0]['data']);
        self::assertSame(120, $charts[0]['chart']->getData()['datasets'][0]['data'][0]);
    }

    public function testShortSeriesAreDrawnAsRecorded(): void
    {
        $phase = $this->phase(SportType::Ergometer, [
            'times' => [10, 20, 30],
            'paces' => [1200, 1210, 1220],
            'strokeRates' => null,
            'heartRates' => null,
            'distances' => null,
        ]);

        self::assertSame([120, 121, 122], $this->charts($phase)[0]['chart']->getData()['datasets'][0]['data']);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function phase(SportType $sport, array $attributes): TrainingPhase
    {
        return TrainingPhaseFactory::createOne($attributes + [
            'training' => TrainingFactory::new(['sport' => $sport]),
            'heartRates' => null,
            'powers' => null,
            'distances' => null,
        ]);
    }

    /**
     * @return list<array{title: string, unit: string, chart: \Symfony\UX\Chartjs\Model\Chart}>
     */
    private function charts(TrainingPhase $phase): array
    {
        return self::getContainer()->get(TrainingPhaseCharts::class)->charts($phase);
    }
}
