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

namespace App\Service\Fit;

use App\Enum\TrainingPhaseIntensity;
use Sportlog\FIT\Profile\Types\Manufacturer;

/**
 * What a device brand writes wrong in its FIT files, and the reading that corrects it.
 * One method per known deviation — when the vendor fixes theirs, delete the method and its test.
 */
final class FitDeviceQuirks
{
    /**
     * Concept2 gets interval workouts right (work = active, rest = rest) but writes the splits of a
     * continuous piece as laps, every one flagged REST. A session cannot be rest alone: all-rest laps
     * are splits, not phases, and the piece is read as a single phase — the session itself.
     *
     * @param list<TrainingPhaseIntensity> $intensities one per lap, in file order
     */
    public static function lapsAreSplits(?int $manufacturer, array $intensities): bool
    {
        return Manufacturer::CONCEPT2 === $manufacturer
            && [] !== $intensities
            && array_all($intensities, static fn (TrainingPhaseIntensity $intensity): bool => TrainingPhaseIntensity::Rest === $intensity);
    }
}
