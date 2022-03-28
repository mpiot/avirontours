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

use function Symfony\Component\String\u;

class DurationManipulator
{
    public static function secondsToDateInterval(int $seconds): \DateInterval
    {
        $splitDuration = self::splitDuration($seconds);

        return new \DateInterval(sprintf('P0Y0M0DT%sH%sM%sS', $splitDuration['hours'], $splitDuration['minutes'], $splitDuration['seconds']));
    }

    public static function dateIntervalToSeconds(\DateInterval $dateInterval): int
    {
        return $dateInterval->h * 3600 + $dateInterval->i * 60 + $dateInterval->s;
    }

    public static function formatSeconds(int $seconds): string
    {
        $splitDuration = self::splitDuration($seconds);
        $hours = u((string) $splitDuration['hours'])->padStart(2, '0')->toString();
        $minutes = u((string) $splitDuration['minutes'])->padStart(2, '0')->toString();

        return sprintf('%s:%s', $hours, $minutes);
    }

    public static function formatTenthSeconds(int $tenthSeconds): string
    {
        $splitDuration = self::splitDuration((int) ($tenthSeconds / 10));
        $hours = u((string) $splitDuration['hours'])->padStart(2, '0')->toString();
        $minutes = u((string) $splitDuration['minutes'])->padStart(2, '0')->toString();
        $seconds = u((string) $splitDuration['seconds'])->padStart(2, '0')->toString();
        $tenthSeconds = $seconds % 10;

        if (0 === $splitDuration['hours']) {
            return sprintf('%s:%s.%s', $minutes, $seconds, $tenthSeconds);
        }

        return sprintf('%s:%s:%s.%s', $hours, $minutes, $seconds, $tenthSeconds);
    }

    private static function splitDuration(int $seconds): array
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $seconds = $seconds % 60;

        return [
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
        ];
    }
}
