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
        if (null === $user || null === $user->getGender() || null === $user->getPhysiology()) {
            return null;
        }

        $isMale = User::GENDER_MALE === $user->getGender();
        $restingHeartRate = $user->getPhysiology()->getRestingHeartRate();
        $maximumHeartRate = $user->getPhysiology()->getMaximumHeartRate();
        if (null === $restingHeartRate || null === $maximumHeartRate || $restingHeartRate >= $maximumHeartRate) {
            return null;
        }

        // One load per phase: integrated over its samples when it kept them — k is exponential, a hard
        // half and an easy half cost more than their average pretends.
        $loads = [];
        foreach ($training->getTrainingPhases() as $phase) {
            $times = $phase->getTimes();
            $heartRates = $phase->getHeartRates();
            if (null === $times || null === $heartRates) {
                if (null !== $phase->getDuration() && null !== $phase->getAverageHeartRate()) {
                    $loads[] = $this->trimp($isMale, $restingHeartRate, $maximumHeartRate, $phase->getDuration() / 600, $phase->getAverageHeartRate());
                }

                continue;
            }

            // Each sample weighs the time since the previous one; times are tenths of a second, 600 to the minute.
            $phaseLoad = 0.0;
            $previous = 0;
            foreach ($heartRates as $index => $heartRate) {
                $time = $times[$index] ?? $previous;
                $phaseLoad += $this->trimp($isMale, $restingHeartRate, $maximumHeartRate, max(0, $time - $previous) / 600, $heartRate);
                $previous = $time;
            }
            $loads[] = $phaseLoad;
        }

        // No phase measured anything: the session average is all that is left to count.
        if ([] === $loads) {
            if (null === $training->getDuration() || null === $training->getAverageHeartRate()) {
                return null;
            }

            $loads[] = $this->trimp($isMale, $restingHeartRate, $maximumHeartRate, $training->getDuration() / 600, $training->getAverageHeartRate());
        }

        return (int) round(array_sum($loads));
    }

    /**
     * T × ΔHR × k(ΔHR) for $minutes spent at $heartRate.
     */
    private function trimp(bool $isMale, int $restingHeartRate, int $maximumHeartRate, float $minutes, int $heartRate): float
    {
        // k = a·e^(b·ΔHR)
        [$a, $b] = $isMale ? [0.64, 1.92] : [0.86, 1.67];

        // ΔHR clamped to [0, 1]: below the declared resting rate costs nothing, above the declared
        // maximum is a full reserve — k is only calibrated on that interval.
        $deltaHeartRate = min(1.0, max(0.0, ($heartRate - $restingHeartRate) / ($maximumHeartRate - $restingHeartRate)));

        return $minutes * $deltaHeartRate * $a * exp($b * $deltaHeartRate);
    }
}
