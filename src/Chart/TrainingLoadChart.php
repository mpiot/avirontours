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

use App\Entity\User;
use App\Repository\TrainingRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class TrainingLoadChart
{
    public function __construct(
        private TrainingRepository $trainingRepository,
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    public function weekly(User $user): ?Chart
    {
        $monday = new \DateTimeImmutable('monday this week');
        $from = $monday->modify('-7 weeks');
        $to = $monday->modify('+6 days')->setTime(23, 59);

        $weeks = [];
        for ($i = 7; $i >= 0; --$i) {
            $weekStart = $monday->modify("-{$i} weeks");
            $weeks[$weekStart->format('o-W')] = ['label' => "S{$weekStart->format('W')}", 'load' => 0];
        }

        $rated = false;
        foreach ($this->trainingRepository->findForUser($user, $from, $to) as $training) {
            $load = $training->getTrainingLoad();
            if (null === $load) {
                continue;
            }

            $rated = true;
            $weeks[$training->getTrainedAt()->format('o-W')]['load'] += $load;
        }

        if (false === $rated) {
            return null;
        }

        $colors = array_fill(0, 7, 'rgba(22, 25, 131, 0.6)');
        $colors[] = '#161983';

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => array_column($weeks, 'label'),
            'datasets' => [
                [
                    'label' => 'Charge',
                    'data' => array_column($weeks, 'load'),
                    'backgroundColor' => $colors,
                ],
            ],
        ]);
        $chart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ]);

        return $chart;
    }
}
