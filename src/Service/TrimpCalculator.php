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

/**
 * Banister's TRIMP: TRIMP = T * ΔHR * k
 * T = duration in minutes
 * ΔHR = (HR exercise – HR rest)/(HR max - HR rest)
 * k (for women) = 0.86e(1.67 * ΔHR)
 * k (for men) = 0.64e(1.92 * ΔHR).
 */
final readonly class TrimpCalculator
{
    public function compute(Training $training): ?int
    {
        $user = $training->getUser();
        if (null === $user) {
            return null;
        }

        $maximumHeartRate = $user->getPhysiology()?->getMaximumHeartRate();
        $restingHeartRate = $user->getPhysiology()?->getRestingHeartRate();
        if (null === $maximumHeartRate || null === $restingHeartRate || $restingHeartRate >= $maximumHeartRate) {
            return null;
        }

        $k = User::GENDER_FEMALE === $user->getGender()
            ? static fn (float $deltaHeartRate): float => 0.86 * exp(1.67 * $deltaHeartRate)
            : static fn (float $deltaHeartRate): float => 0.64 * exp(1.92 * $deltaHeartRate)
        ;

        // Integrate over the records whenever they exist: k is exponential, so a hard half and an
        // easy half cost more than their average pretends — the mean form below, kept for sessions
        // that only carry an average (Concept2 summaries, manual entries), underestimates intervals.
        $trimp = 0.0;
        $hasSeries = false;
        foreach ($training->getTrainingPhases() as $phase) {
            $times = $phase->getTimes();
            $heartRates = $phase->getHeartRates();
            if (null === $times || null === $heartRates) {
                continue;
            }

            $hasSeries = true;
            $previous = 0;
            foreach ($heartRates as $index => $heartRate) {
                $time = $times[$index] ?? $previous;
                // Times are tenths of a second and T is in minutes: 600 tenths to the minute.
                $t = max(0, $time - $previous) / 600;
                $previous = $time;

                $deltaHeartRate = $this->deltaHeartRate($heartRate, $restingHeartRate, $maximumHeartRate);
                $trimp += $t * $deltaHeartRate * $k($deltaHeartRate);
            }
        }

        if (false === $hasSeries) {
            $averageHeartRate = $training->getAverageHeartRate();
            $duration = $training->getDuration();
            if (null === $averageHeartRate || null === $duration) {
                return null;
            }

            $t = $duration / 600;
            $deltaHeartRate = $this->deltaHeartRate($averageHeartRate, $restingHeartRate, $maximumHeartRate);
            $trimp = $t * $deltaHeartRate * $k($deltaHeartRate);
        }

        return (int) round($trimp);
    }

    private function deltaHeartRate(int $heartRate, int $restingHeartRate, int $maximumHeartRate): float
    {
        // Clamped to [0, 1]: below the declared resting rate costs nothing, above the declared
        // maximum is a full reserve — k is only calibrated on that interval.
        return min(1.0, max(0.0, ($heartRate - $restingHeartRate) / ($maximumHeartRate - $restingHeartRate)));
    }
}
