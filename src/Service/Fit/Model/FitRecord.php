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

/**
 * One sample, in FIT profile units: semicircles, metres, m/s, bpm, rpm (with the fractional part), watts.
 */
final readonly class FitRecord
{
    public function __construct(
        public \DateTimeImmutable $recordedAt,
        public ?int $latitude = null,
        public ?int $longitude = null,
        public ?float $altitude = null,
        public ?float $distance = null,
        public ?float $speed = null,
        public ?int $heartRate = null,
        public ?float $cadence = null,
        public ?int $power = null,
    ) {
    }
}
