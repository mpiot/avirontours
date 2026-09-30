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
    public function __construct(private ChartBuilderInterface $chartBuilder)
    {
    }

    /**
     * @return list<array{title: string, unit: string, chart: Chart}>
     */
    public function charts(TrainingPhase $trainingPhase): array
    {
        if (
            null === $trainingPhase->getTimes()
            || null === $trainingPhase->getPaces()
            || null === $trainingPhase->getStrokeRates()
        ) {
            return [];
        }

        $labels = array_map(
            static fn (int $tenthSeconds): string => DurationManipulator::formatSecondsAsMinutesSeconds((int) round($tenthSeconds / 10)),
            $trainingPhase->getTimes()
        );

        $series = [
            [
                'title' => 'Allure',
                'unit' => 'pace',
                // Do not display 0
                'data' => array_map(
                    static fn (int $tenthSecondsPer500): ?int => 0 !== $tenthSecondsPer500 ? (int) round($tenthSecondsPer500 / 10) : null,
                    $trainingPhase->getPaces()
                ),
                'color' => '#4f46e5',
                'average' => null === $trainingPhase->getPace() ? null : $trainingPhase->getPace() / 10,
                'yScale' => ['reverse' => true],
            ],
            [
                'title' => 'Cadence',
                'unit' => 'spm',
                // Do not display 0
                'data' => array_map(static fn (int $strokeRate): ?int => 0 !== $strokeRate ? $strokeRate : null, $trainingPhase->getStrokeRates()),
                'color' => '#475569',
                'average' => $trainingPhase->getStrokeRate(),
                'yScale' => [],
            ],
        ];

        if (null !== $trainingPhase->getHeartRates()) {
            $series[] = [
                'title' => 'Fréquence cardiaque',
                'unit' => 'bpm',
                'data' => $trainingPhase->getHeartRates(),
                'color' => '#be123c',
                'average' => $trainingPhase->getAverageHeartRate(),
                'yScale' => [],
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
     * @param list<int|null>       $data
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
                'y' => array_merge(['type' => 'linear', 'position' => 'left', 'grace' => '20%', 'ticks' => ['precision' => 0]], $yScale),
            ],
        ];

        $chart->setOptions($options);

        return $chart;
    }
}
