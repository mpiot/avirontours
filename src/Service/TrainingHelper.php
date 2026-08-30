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
use App\Enum\SportSpecificity;
use App\Repository\TrainingRepository;
use App\Util\MathHelper;

readonly class TrainingHelper
{
    public function __construct(
        private TrainingRepository $trainingRepository,
        private TrainingLoadModel $trainingLoadModel,
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

            // Group trainings per specificity, seeded in enum order: that order is the colour scale.
            $categorizedTrainings = [];
            foreach (SportSpecificity::cases() as $specificity) {
                $categorizedTrainings[$specificity->value] = [
                    'specificity' => $specificity,
                    'sessions' => 0,
                    'duration' => 0,
                    'distance' => 0,
                    'share' => 0,
                ];
            }

            foreach ($weekTrainings as $training) {
                $specificity = $training->getSport()->specificity()->value;
                ++$categorizedTrainings[$specificity]['sessions'];
                $categorizedTrainings[$specificity]['duration'] += (int) round($training->getDuration() / 10);
                $categorizedTrainings[$specificity]['distance'] += $training->getDistance() ?? 0;
            }

            // Only the specificities practised: a segment at zero has no width to show.
            $categorizedTrainings = array_values(array_filter(
                $categorizedTrainings,
                static fn (array $categorizedTraining): bool => $categorizedTraining['sessions'] > 0,
            ));

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
                    'specificities' => $categorizedTrainings,
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
        foreach ($acuteTrainings as $training) {
            $load = $training->getTrainingLoad();
            if (null !== $load) {
                $acuteLoads[] = $load;
            }
        }

        // A window without any session is a real zero; one trained but never rated is unknown.
        $acute = [] !== $acuteLoads ? array_sum($acuteLoads) : ([] === $acuteTrainings ? 0 : null);

        $state = $this->trainingLoadModel->daily($user, $today)[$today->format('Y-m-d')] ?? null;

        return [
            'hasRecentTrainings' => [] !== $trainings,
            'volumes' => $volumes,
            'load' => [
                'acute' => $acute,
                'rated' => \count($acuteLoads),
                'fitness' => $state['fitness'] ?? null,
                'fatigue' => $state['fatigue'] ?? null,
                'form' => null !== $state ? $state['fitness'] - $state['fatigue'] : null,
            ],
        ];
    }
}
