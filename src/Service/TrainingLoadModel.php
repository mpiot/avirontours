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

use App\Entity\User;
use App\Repository\TrainingRepository;
use App\Util\MathHelper;

/**
 * Banister's fitness–fatigue model over Foster's session load:
 * two exponential averages of the daily load, with time constants of 42 and 7 days.
 */
final readonly class TrainingLoadModel
{
    public const int FITNESS_TIME_CONSTANT = 42;
    public const int FATIGUE_TIME_CONSTANT = 7;

    public function __construct(private TrainingRepository $trainingRepository)
    {
    }

    /**
     * One entry per day, from the member's first rated session up to $to. Fitness and fatigue are
     * in weekly-equivalent load so they read on the scale of a week's total.
     *
     * @return array<string, array{load: int, fitness: float, fatigue: float}> keyed by Y-m-d
     */
    public function daily(User $user, \DateTimeImmutable $to): array
    {
        $loads = [];
        foreach ($this->trainingRepository->findRatedForUser($user, $to->setTime(23, 59, 59)) as $training) {
            $load = $training->getTrainingLoad();
            if (null === $load) {
                continue;
            }

            $day = $training->getTrainedAt()->format('Y-m-d');
            $loads[$day] = ($loads[$day] ?? 0) + $load;
        }

        if ([] === $loads) {
            return [];
        }

        $daily = [];
        $days = new \DatePeriod(new \DateTimeImmutable(array_key_first($loads)), new \DateInterval('P1D'), $to, \DatePeriod::INCLUDE_END_DATE);
        foreach ($days as $day) {
            $daily[$day->format('Y-m-d')] = $loads[$day->format('Y-m-d')] ?? 0;
        }

        $fitness = MathHelper::ewma(array_values($daily), self::FITNESS_TIME_CONSTANT);
        $fatigue = MathHelper::ewma(array_values($daily), self::FATIGUE_TIME_CONSTANT);

        $states = [];
        foreach (array_keys($daily) as $i => $day) {
            $states[$day] = ['load' => $daily[$day], 'fitness' => $fitness[$i] * 7, 'fatigue' => $fatigue[$i] * 7];
        }

        return $states;
    }
}
