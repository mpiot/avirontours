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
            $weekTrainings = array_filter(
                $trainings,
                fn (Training $training): bool => $week->format('W') === $training->getTrainedAt()->format('W'),
            );

            $categorizedTrainings = [];
            foreach ($weekTrainings as $training) {
                if (false === \array_key_exists($training->getSport()->value, $categorizedTrainings)) {
                    $categorizedTrainings[$training->getSport()->value] = [
                        'sport' => $training->getSport(),
                        'sessions' => 0,
                        'duration' => 0,
                        'distance' => 0,
                        'ratio' => 0,
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

            $duration = array_reduce($weekTrainings, fn (int $carry, Training $training): int => $carry + $training->getDuration(), 0);
            $duration = (int) round($duration / 10);

            // Define ratio
            foreach ($categorizedTrainings as &$categorizedTraining) {
                $categorizedTraining['ratio'] = round($categorizedTraining['duration'] / $duration, 2);
            }

            $data[] = [
                'week' => $week,
                'trainings' => $weekTrainings,
                'summary' => [
                    'sessions' => \count($weekTrainings),
                    'duration' => $duration,
                    'sports' => $categorizedTrainings,
                ],
            ];
        }

        return $data;
    }
}
