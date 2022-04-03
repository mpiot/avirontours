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

use App\Entity\User;
use App\Repository\LogbookEntryRepository;
use App\Service\ArrayNormalizer;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class LogbookChart
{
    public function __construct(
        private LogbookEntryRepository $logbookEntryRepository,
        private ChartBuilderInterface $chartBuilder,
        private ArrayNormalizer $normalizer
    ) {
    }

    public function chart(User $user)
    {
        $logbookCount = $this->logbookEntryRepository->findStatsByMonth($user);
        $logbookCount = $this->normalizer->fillMissingMonths(
            $logbookCount,
            (new \DateTime('-11 months')),
            (new \DateTime()),
            ['distance' => 0, 'session' => 0]
        );
        $logbookCount = $this->normalizer->normalize($logbookCount);
        $logbookCount = $this->normalizer->formatMonthNames($logbookCount);

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => $logbookCount['months'],
            'datasets' => [
                [
                    'label' => 'Distances',
                    'yAxisID' => 'distances',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                    'data' => $logbookCount['distances'],
                ],
                [
                    'label' => 'Sessions',
                    'yAxisID' => 'sessions',
                    'backgroundColor' => 'rgb(235,54,54, 0.6)',
                    'borderColor' => 'rgb(235,54,54, 1)',
                    'borderWidth' => 1,
                    'data' => $logbookCount['sessions'],
                ],
            ],
        ]);

        $chart->setOptions([
            'scales' => [
                'distances' => [
                    'type' => 'linear',
                    'position' => 'left',
                    'ticks' => [
                        'beginAtZero' => true,
                        'precision' => 0,
                    ],
                ],
                'sessions' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'ticks' => [
                        'beginAtZero' => true,
                        'precision' => 0,
                    ],
                ],
            ],
        ]);

        return $chart;
    }
}
