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

namespace App\Service;

use App\Entity\Training;
use App\Entity\User;
use App\Repository\TrainingRepository;
use App\Util\MathHelper;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class TrainingHelper
{
    public function __construct(
        private TrainingRepository $trainingRepository,
        private TranslatorInterface $translator,
    ) {
    }

    public function getTrainingsSummary(User $user, \DateTimeInterface $startAt, \DateTimeInterface $endAt): array
    {
        $weeks = new \DatePeriod(
            $startAt,
            new \DateInterval('P1W'),
            $endAt,
            \DatePeriod::INCLUDE_END_DATE
        );

        $data = [];
        $trainings = $this->trainingRepository->findForUser($user, $startAt, $endAt);
        foreach ($weeks as $week) {
            // Get week trainings
            $weekTrainings = array_filter(
                $trainings,
                static fn (Training $training): bool => $week->format('W') === $training->getTrainedAt()->format('W'),
            );

            // Group trainings per category of sport
            $categorizedTrainings = [];
            foreach ($weekTrainings as $training) {
                if (false === \array_key_exists($training->getSport()->value, $categorizedTrainings)) {
                    $categorizedTrainings[$training->getSport()->value] = [
                        'sport' => $training->getSport(),
                        'sessions' => 0,
                        'duration' => 0,
                        'distance' => 0,
                        'share' => 0,
                    ];
                }

                ++$categorizedTrainings[$training->getSport()->value]['sessions'];
                $categorizedTrainings[$training->getSport()->value]['duration'] += (int) round($training->getDuration() / 10);
                $categorizedTrainings[$training->getSport()->value]['distance'] += $training->getDistance();
            }

            usort(
                $categorizedTrainings,
                fn (array $a, array $b): int => $this->translator->trans($a['sport']->label()) <=> $this->translator->trans($b['sport']->label())
            );

            // Calculate total duration
            $duration = array_sum(array_column($categorizedTrainings, 'duration'));

            // Calculate shares (with sum always equals to 100)
            $shares = MathHelper::shares(array_column($categorizedTrainings, 'duration'));
            foreach ($shares as $key => $share) {
                $categorizedTrainings[$key]['share'] = $share;
            }

            // Foster's load only exists where the member rated the session
            $load = null;
            $ratedTrainings = array_filter(
                $weekTrainings,
                static fn (Training $training): bool => null !== $training->getTrainingLoad(),
            );
            if ([] !== $ratedTrainings) {
                $load = array_sum(
                    array_map(
                        static fn (Training $training): int => $training->getTrainingLoad(),
                        $ratedTrainings,
                    )
                );
            }

            $data[] = [
                'week' => $week,
                'trainings' => $weekTrainings,
                'summary' => [
                    'sessions' => \count($weekTrainings),
                    'duration' => $duration,
                    'sports' => $categorizedTrainings,
                    'load' => $load,
                ],
            ];
        }

        return $data;
    }

    public function getDashboardKpis(User $user): array
    {
        $today = new \DateTimeImmutable('today');
        $acuteFrom = $today->modify('-6 days');
        $chronicFrom = $today->modify('-27 days');
        $trainings = $this->trainingRepository->findForUser($user, $chronicFrom, $today->setTime(23, 59, 59));

        $acuteTrainings = array_filter(
            $trainings,
            static fn (Training $training): bool => $training->getTrainedAt() >= $acuteFrom,
        );

        $volumes = ['sessions' => \count($acuteTrainings), 'duration' => 0, 'distance' => 0];
        foreach ($acuteTrainings as $training) {
            $volumes['duration'] += (int) round($training->getDuration() / 10);
            $volumes['distance'] += $training->getDistance() ?? 0;
        }

        $acuteLoads = [];
        $chronicLoads = [];
        foreach ($trainings as $training) {
            $load = $training->getTrainingLoad();
            if (null === $load) {
                continue;
            }

            $chronicLoads[] = $load;
            if ($training->getTrainedAt() >= $acuteFrom) {
                $acuteLoads[] = $load;
            }
        }

        // A window without any session is a real zero; one trained but never rated is unknown.
        $acute = [] !== $acuteLoads ? array_sum($acuteLoads) : ([] === $acuteTrainings ? 0 : null);
        $chronic = [] !== $chronicLoads ? array_sum($chronicLoads) / 4.0 : null;
        $ratio = null !== $acute && null !== $chronic && $chronic > 0 ? $acute / $chronic : null;

        // Coupled acute:chronic workload ratio (Gabbett): 0.8–1.3 is the usual training zone.
        $zone = null === $ratio ? null : match (true) {
            $ratio < 0.8 => 'Charge allégée',
            $ratio <= 1.3 => 'Zone habituelle',
            default => 'Charge élevée',
        };

        return [
            'hasRecentTrainings' => [] !== $trainings,
            'volumes' => $volumes,
            'load' => ['acute' => $acute, 'chronic' => $chronic, 'ratio' => $ratio, 'zone' => $zone],
        ];
    }
}
