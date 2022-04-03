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

namespace App\Chart;

use App\Entity\TrainingPhase;
use App\Util\DurationManipulator;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class TrainingPhaseChart
{
    public function __construct(private ChartBuilderInterface $chartBuilder)
    {
    }

    public function chart(TrainingPhase $trainingPhase)
    {
        $datasets = [
            [
                'label' => 'Pace',
                'yAxisID' => 'pace',
                'data' => array_map(fn (int $tenthSecondsPer500) => (int) round($tenthSecondsPer500 / 10), $trainingPhase->getPaces()),
                'borderColor' => 'rgb(124,181,236, 1)',
            ],
            [
                'label' => 'SPM',
                'yAxisID' => 'spm',
                'data' => $trainingPhase->getStrokeRates(),
                'borderColor' => 'rgba(67, 67, 72, 1)',
            ],
        ];

        $scales = [
            'x' => [
                'grid' => [
                    'display' => false,
                ],
            ],
            'pace' => [
                'type' => 'linear',
                'position' => 'left',
                'min' => 90,
                'max' => 240,
                'reverse' => true,
                'title' => [
                    'display' => true,
                    'text' => 'Pace',
                ],
                'ticks' => [
                    'precision' => 0,
                    'count' => 6,
                ],
            ],
            'spm' => [
                'type' => 'linear',
                'position' => 'right',
                'title' => [
                    'display' => true,
                    'text' => 'Stroke Rate',
                ],
                'ticks' => [
                    'precision' => 0,
                    'count' => 6,
                    'stepSize' => 10,
                ],
            ],
        ];

        if (null !== $trainingPhase->getHeartRates()) {
            $datasets[] = [
                'label' => 'Heart rate',
                'yAxisID' => 'hr',
                'data' => $trainingPhase->getHeartRates(),
                'borderColor' => 'rgb(255,0,0, 1)',
            ];

            $scales['hr'] = [
                'type' => 'linear',
                'display' => false,
                'position' => 'right',
                'min' => 25,
                'title' => [
                    'display' => true,
                    'text' => 'Heart Rate',
                ],
                'ticks' => [
                    'precision' => 0,
                    'count' => 6,
                    'stepSize' => 25,
                ],
            ];
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => array_map(fn (int $tenthSeconds) => DurationManipulator::formatSeconds((int) round($tenthSeconds / 10)), $trainingPhase->getTimes()),
            'datasets' => $datasets,
        ]);

        $chart->setOptions([
            'datasets' => [
                'line' => [
                    'borderWidth' => 1,
                    'pointRadius' => 0,
                ],
            ],
            'plugins' => [
                'tooltip' => [
                    'intersect' => false,
                    'mode' => 'index',
                ],
            ],
            'scales' => $scales,
        ]);

        return $chart;
    }
}
