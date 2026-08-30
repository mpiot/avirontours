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
use App\Service\TrainingLoadModel;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * Eight weeks of Foster load, under the fitness and fatigue the member carries into each of them.
 */
final readonly class TrainingLoadChart
{
    public function __construct(
        private TrainingLoadModel $trainingLoadModel,
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    public function weekly(User $user): ?Chart
    {
        $today = new \DateTimeImmutable('today');
        $monday = new \DateTimeImmutable('monday this week');
        $states = $this->trainingLoadModel->daily($user, $today);

        $labels = [];
        $loads = [];
        $fitness = [];
        $fatigue = [];
        for ($i = 7; $i >= 0; --$i) {
            $weekStart = $monday->modify("-{$i} weeks");

            $load = 0;
            for ($day = 0; $day < 7; ++$day) {
                $load += $states[$weekStart->modify("+{$day} days")->format('Y-m-d')]['load'] ?? 0;
            }

            // The running week has no Sunday yet, so its state is read at today.
            $state = $states[min($weekStart->modify('+6 days'), $today)->format('Y-m-d')] ?? ['fitness' => 0.0, 'fatigue' => 0.0];

            $labels[] = "S{$weekStart->format('W')}";
            $loads[] = $load;
            $fitness[] = (int) round($state['fitness']);
            $fatigue[] = (int) round($state['fatigue']);
        }

        if (array_all($loads, static fn (int $load): bool => 0 === $load)) {
            return null;
        }

        $colors = array_fill(0, 7, 'rgba(22, 25, 131, 0.6)');
        $colors[] = '#161983';

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Charge',
                    'data' => $loads,
                    'backgroundColor' => $colors,
                    'order' => 1,
                ],
                [
                    'type' => 'line',
                    'label' => 'Condition',
                    'data' => $fitness,
                    'borderColor' => '#475569',
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'tension' => 0.3,
                ],
                [
                    'type' => 'line',
                    'label' => 'Fatigue',
                    'data' => $fatigue,
                    'borderColor' => '#be123c',
                    'borderDash' => [4, 4],
                    'borderWidth' => 1.5,
                    'pointRadius' => 0,
                    'tension' => 0.3,
                ],
            ],
        ]);
        $chart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
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
