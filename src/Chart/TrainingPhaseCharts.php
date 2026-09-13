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

namespace App\Chart;

use App\Entity\TrainingPhase;
use App\Util\DurationManipulator;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class TrainingPhaseCharts
{
    // Series recorded at 1 Hz are averaged down to this many points; per-stroke series mostly fit already.
    private const int MAX_POINTS = 600;

    public function __construct(private ChartBuilderInterface $chartBuilder)
    {
    }

    /**
     * One chart per series the phase carries: pace (in the unit of the sport), cadence, heart rate, power.
     *
     * @return list<array{title: string, unit: string, chart: Chart}>
     */
    public function charts(TrainingPhase $trainingPhase): array
    {
        $times = $trainingPhase->getTimes();
        if (null === $times || [] === $times) {
            return [];
        }

        $bucket = max(1, (int) ceil(\count($times) / self::MAX_POINTS));
        $labels = array_map(
            static fn (array $chunk): string => DurationManipulator::formatSecondsAsMinutesSeconds((int) round($chunk[0] / 10)),
            array_chunk($times, $bucket),
        );

        $series = [];

        $paces = $trainingPhase->getPaces();
        $speedUnit = $trainingPhase->getTraining()?->getSport()?->speedUnit();
        if (null !== $paces && null !== $speedUnit) {
            $series[] = self::paceSeries($speedUnit, self::decimate($paces, $bucket), $trainingPhase->getPace());
        }

        if (null !== $trainingPhase->getStrokeRates()) {
            $series[] = [
                'title' => 'Cadence',
                'unit' => 'spm',
                'data' => self::decimate($trainingPhase->getStrokeRates(), $bucket),
                'color' => '#475569',
                'average' => $trainingPhase->getStrokeRate(),
                'yScale' => ['ticks' => ['precision' => 0, 'stepSize' => 10]],
            ];
        }

        if (null !== $trainingPhase->getHeartRates()) {
            $series[] = [
                'title' => 'Fréquence cardiaque',
                'unit' => 'bpm',
                'data' => self::decimate($trainingPhase->getHeartRates(), $bucket),
                'color' => '#be123c',
                'average' => $trainingPhase->getAverageHeartRate(),
                'yScale' => ['min' => 40, 'ticks' => ['precision' => 0, 'stepSize' => 25]],
            ];
        }

        if (null !== $trainingPhase->getPowers()) {
            $series[] = [
                'title' => 'Puissance',
                'unit' => 'W',
                'data' => self::decimate($trainingPhase->getPowers(), $bucket),
                'color' => '#b45309',
                'average' => $trainingPhase->getAveragePower(),
                'yScale' => ['min' => 0, 'ticks' => ['precision' => 0]],
            ];
        }

        $lastIndex = \count($series) - 1;

        return array_map(
            fn (int $index, array $serie): array => [
                'title' => $serie['title'],
                'unit' => $serie['unit'],
                'chart' => $this->createChart(
                    $labels,
                    $serie['data'],
                    $serie['color'],
                    $serie['average'],
                    $serie['yScale'],
                    $index === $lastIndex,
                ),
            ],
            array_keys($series),
            $series,
        );
    }

    /**
     * @param list<int|float>      $data
     * @param array<string, mixed> $yScale
     */
    private function createChart(
        array $labels,
        array $data,
        string $color,
        int|float|null $average,
        array $yScale,
        bool $withTimeTicks,
    ): Chart {
        $datasets = [[
            'data' => $data,
            'borderColor' => $color,
        ]];

        if (null !== $average) {
            $datasets[] = [
                'data' => array_fill(0, \count($data), $average),
                'borderColor' => $color,
                'borderDash' => [4, 4],
            ];
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $labels,
            'datasets' => $datasets,
        ]);

        $options = [
            'maintainAspectRatio' => false,
            'datasets' => ['line' => ['borderWidth' => 1, 'pointRadius' => 0]],
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => ['intersect' => false, 'mode' => 'index'],
            ],
            'scales' => [
                'x' => [
                    'grid' => ['display' => false],
                    'ticks' => ['display' => $withTimeTicks, 'maxTicksLimit' => 12],
                ],
                'y' => array_merge(['type' => 'linear', 'position' => 'left'], $yScale),
            ],
        ];

        $chart->setOptions($options);

        return $chart;
    }

    /**
     * Paces are stored in tenths of a second per 500 m whatever the sport; the chart speaks the sport's unit.
     *
     * @param list<int> $paces
     *
     * @return array{title: string, unit: string, data: list<int|float>, color: string, average: int|float|null, yScale: array<string, mixed>}
     */
    private static function paceSeries(string $speedUnit, array $paces, ?int $averagePace): array
    {
        if ('km/h' === $speedUnit) {
            $toSpeed = static fn (int $pace): float => 0 === $pace ? 0.0 : round(18000 / $pace, 1);

            return [
                'title' => 'Vitesse',
                'unit' => 'speed',
                'data' => array_map($toSpeed, $paces),
                'color' => '#4f46e5',
                'average' => null === $averagePace ? null : $toSpeed($averagePace),
                'yScale' => ['min' => 0, 'ticks' => ['precision' => 1]],
            ];
        }

        // Tenths per 500 m to seconds per 500 m, per km or per 100 m.
        $divisor = match ($speedUnit) {
            '/km' => 5,
            '/100m' => 50,
            default => 10,
        };
        $toSeconds = static fn (int $pace): int => (int) round($pace / $divisor);

        return [
            'title' => 'Allure',
            'unit' => 'pace',
            'data' => array_map($toSeconds, $paces),
            'color' => '#4f46e5',
            'average' => null === $averagePace ? null : $toSeconds($averagePace),
            'yScale' => ('/500m' === $speedUnit ? ['min' => 60] : []) + ['reverse' => true, 'ticks' => ['precision' => 0]],
        ];
    }

    /**
     * @param list<int> $values
     *
     * @return list<int>
     */
    private static function decimate(array $values, int $bucket): array
    {
        if (1 === $bucket) {
            return $values;
        }

        return array_map(
            static fn (array $chunk): int => (int) round(array_sum($chunk) / \count($chunk)),
            array_chunk($values, $bucket),
        );
    }
}
