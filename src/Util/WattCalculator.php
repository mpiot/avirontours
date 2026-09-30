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

namespace App\Util;

/**
 * Converts a rowing pace (tenths of a second per 500 m) to an average power.
 *
 * @see https://www.concept2.com/indoor-rowers/training/calculators/watts-calculator
 */
class WattCalculator
{
    public static function calculateFromPace(int $pace): int
    {
        $paceInSeconds = $pace / 10;

        return (int) round(2.8 / ($paceInSeconds / 500) ** 3);
    }
}
