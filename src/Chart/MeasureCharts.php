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
use App\Enum\MeasureType;
use App\Repository\MeasureRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * One chart per type a member measured over the last year: the measures as points, their seven-day trend as a line.
 */
final readonly class MeasureCharts
{
    private const int TREND_WINDOW_DAYS = 7;

    public function __construct(
        private MeasureRepository $measureRepository,
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    /**
     * @return list<array{title: string, chart: Chart}>
     */
    public function charts(User $user): array
    {
        $from = new \DateTimeImmutable('today')->modify('-1 year');
        $to = new \DateTimeImmutable('today')->setTime(23, 59, 59);
        $formatter = new \IntlDateFormatter('fr', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, pattern: 'd MMM');

        $measured = [];
        foreach ($this->measureRepository->findForUser($user, $from, $to) as $measure) {
            $measured[$measure->getType()->value][$measure->getMeasuredAt()->format('Y-m-d')] = $measure->getValue();
        }

        // The axis stays daily even on sparse measures, or two points three months apart would touch.
        $days = [];
        $labels = [];
        foreach (new \DatePeriod($from, new \DateInterval('P1D'), $to) as $day) {
            $days[] = $day->format('Y-m-d');
            $labels[] = (string) $formatter->format($day);
        }

        $charts = [];
        foreach (MeasureType::cases() as $type) {
            if (false === \array_key_exists($type->value, $measured)) {
                continue;
            }

            $values = array_map(static fn (string $day): ?float => $measured[$type->value][$day] ?? null, $days);

            $charts[] = [
                'title' => $type->label(),
                'chart' => $this->createChart($type, $labels, $values),
            ];
        }

        return $charts;
    }

    /**
     * @param list<string>     $labels
     * @param list<float|null> $values
     */
    private function createChart(MeasureType $type, array $labels, array $values): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    // Points only: a measure every ten days must not pass for a continuous series.
                    'label' => 'Mesures',
                    'data' => $values,
                    'backgroundColor' => $type->color(),
                    'showLine' => false,
                    // A year of daily measures is one point per pixel: anything bigger than a speck buries the trend.
                    'pointRadius' => 1.5,
                    'pointHoverRadius' => 4,
                ],
                [
                    'label' => 'Tendance 7 jours',
                    'data' => self::movingAverage($values),
                    'borderColor' => $type->color(),
                    'borderWidth' => 2,
                    'pointRadius' => 0,
                    'pointStyle' => 'line',
                    'spanGaps' => false,
                ],
            ],
        ]);
        $chart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    // A dot for the measures, a stroke for the trend: the legend shows the marks as they are drawn.
                    'labels' => [
                        'usePointStyle' => true,
                    ],
                ],
                'tooltip' => [
                    'intersect' => false,
                    'mode' => 'index',
                ],
            ],
            'scales' => [
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                    'ticks' => [
                        // Horizontal: Chart.js skips what does not fit rather than tilting a year of dates.
                        'maxRotation' => 0,
                        'autoSkipPadding' => 16,
                    ],
                ],
                // No beginAtZero: a weight axis starting at zero would flatten every real variation.
                'y' => [
                    'title' => [
                        'display' => true,
                        'text' => $type->getUnit(),
                    ],
                ],
            ],
        ]);

        return $chart;
    }

    /**
     * Seven-day moving average: for each day, the mean of the measures taken that day and the six before.
     *
     * Leans on `$values` holding exactly one slot per day, oldest first: an index is a day, so the window
     * is a plain slice. Empty days are skipped inside the window; a window with no measure at all gives
     * null, which is what breaks the trend line after a week without measuring.
     *
     * @param list<float|null> $values
     *
     * @return list<float|null>
     */
    private static function movingAverage(array $values): array
    {
        $trend = [];
        foreach (array_keys($values) as $index) {
            // Six days back, clamped at the first slot: the window ends on this day and never reaches past it.
            $start = max(0, $index - self::TREND_WINDOW_DAYS + 1);
            $window = array_filter(
                \array_slice($values, $start, $index - $start + 1),
                static fn (?float $value): bool => null !== $value,
            );

            // Average what was measured, however few days that is.
            $trend[] = [] === $window ? null : round(array_sum($window) / \count($window), 1);
        }

        return $trend;
    }
}
