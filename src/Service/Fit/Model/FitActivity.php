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
 * The single session of an activity file: the session as a whole, and the laps it is cut into, each
 * carrying the samples that fall inside its window. Sport codes raw FIT.
 */
final readonly class FitActivity
{
    /**
     * @param list<FitSegment> $laps sorted by start; the session itself when the file has none
     */
    public function __construct(
        public ?string $device,
        public ?int $sport,
        public ?int $subSport,
        public \DateTimeImmutable $startedAt,
        public ?\DateTimeImmutable $localStartedAt,
        public FitSegment $session,
        public array $laps,
    ) {
    }
}
