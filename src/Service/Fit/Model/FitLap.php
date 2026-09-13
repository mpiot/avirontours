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

namespace App\Service\Fit\Model;

use App\Enum\TrainingPhaseIntensity;

/**
 * Times in seconds, distance in metres.
 */
final readonly class FitLap
{
    public function __construct(
        public \DateTimeImmutable $startedAt,
        public \DateTimeImmutable $endedAt,
        public ?float $totalTimerTime,
        public ?float $totalElapsedTime,
        public ?float $totalDistance,
        public TrainingPhaseIntensity $intensity = TrainingPhaseIntensity::Active,
        public ?int $avgHeartRate = null,
        public ?int $maxHeartRate = null,
        public ?int $avgCadence = null,
        public ?int $avgPower = null,
    ) {
    }
}
