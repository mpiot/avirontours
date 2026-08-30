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
use App\Enum\SportSpecificity;
use App\Repository\TrainingRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

use function Symfony\Component\String\u;

/**
 * A member's monthly training volume over the last twelve months, stacked by sport specificity.
 */
final readonly class TrainingVolumeChart
{
    public function __construct(
        private TrainingRepository $trainingRepository,
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    public function monthly(User $user): ?Chart
    {
        $from = new \DateTimeImmutable('first day of this month')->modify('-11 months')->setTime(0, 0);
        $to = new \DateTimeImmutable('today')->setTime(23, 59, 59);
        $formatter = new \IntlDateFormatter('fr', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, pattern: 'MMM');

        $durations = [];
        $labels = [];
        foreach (new \DatePeriod($from, new \DateInterval('P1M'), $to) as $month) {
            $durations[$month->format('Y-m')] = array_fill_keys(array_column(SportSpecificity::cases(), 'value'), 0);
            $labels[] = u((string) $formatter->format($month))->title()->toString();
        }

        $trainings = $this->trainingRepository->findForUser($user, $from, $to);
        if ([] === $trainings) {
            return null;
        }

        foreach ($trainings as $training) {
            $durations[$training->getTrainedAt()->format('Y-m')][$training->getSport()->specificity()->value] += $training->getDuration();
        }

        // Every specificity, practised or not: a band at zero is the message, not a dead legend entry.
        $datasets = [];
        foreach (SportSpecificity::cases() as $specificity) {
            $datasets[] = [
                'label' => $specificity->label(),
                // Durations are stored in tenths of a second, so 36 000 of them make an hour.
                'data' => array_values(array_map(
                    static fn (array $month): float => round($month[$specificity->value] / 36000, 1),
                    $durations,
                )),
                'backgroundColor' => $specificity->color(),
                'borderColor' => '#fff',
                'borderWidth' => 2,
            ];
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => $labels,
            'datasets' => $datasets,
        ]);
        $chart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                    'grid' => [
                        'display' => false,
                    ],
                ],
                'y' => [
                    'stacked' => true,
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Heures',
                    ],
                ],
            ],
        ]);

        return $chart;
    }
}
