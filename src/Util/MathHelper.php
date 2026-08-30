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

class MathHelper
{
    /**
     * Whole shares of `$total` that sum to exactly it, in the key order they came in.
     *
     * @param array<TKey, int|float> $values
     *
     * @return array<TKey, int>
     */
    public static function shares(array $values, int $total = 100): array
    {
        $sum = array_sum($values);
        if ($sum <= 0) {
            return array_fill_keys(array_keys($values), 0);
        }

        $shares = array_map(
            static fn (int|float $value): int => (int) floor($value * $total / $sum),
            $values
        );

        // floor() loses at most one unit per share: hand them back to the largest values,
        // where a +1 shifts the ratio the least.
        $remainder = $total - array_sum($shares);
        arsort($values);
        foreach (\array_slice(array_keys($values), 0, $remainder) as $key) {
            ++$shares[$key];
        }

        return $shares;
    }

    /**
     * Exponentially weighted moving average, one value out per value in, seeded at zero.
     *
     * @param list<int|float> $values oldest first
     *
     * @return list<float>
     */
    public static function ewma(array $values, int $timeConstant): array
    {
        $average = 0.0;
        $averages = [];
        foreach ($values as $value) {
            $average += ($value - $average) / $timeConstant;
            $averages[] = $average;
        }

        return $averages;
    }
}
