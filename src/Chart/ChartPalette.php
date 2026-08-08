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

namespace App\Chart;

/**
 * Series colours: a saturated scale of its own.
 *
 * Used in Chart and Training list (sport white icon background).
 */
final class ChartPalette
{
    public const string INDIGO = '#4f46e5';

    public const string TEAL = '#0f766e';

    public const string ROSE = '#be123c';

    public const string AMBER = '#a16207';

    public const string ORANGE = '#c2410c';

    public const string SKY = '#0369a1';

    public const string LIME = '#4d7c0f';

    public const string GREEN = '#15803d';

    public const string FUCHSIA = '#a21caf';

    public const string SLATE = '#475569';

    public static function translucent(string $color, float $alpha = 0.6): string
    {
        [$red, $green, $blue] = sscanf($color, '#%02x%02x%02x');

        return \sprintf('rgba(%d, %d, %d, %s)', $red, $green, $blue, $alpha);
    }
}
