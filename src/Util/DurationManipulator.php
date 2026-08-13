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
    public static function dateIntervalToTenthSeconds(?\DateInterval $dateInterval): ?int
    {
        if (null === $dateInterval) {
            return null;
        }

        return (int) round(
            $dateInterval->h * 36000
            + $dateInterval->i * 600
            + $dateInterval->s
            + $dateInterval->f / 10000
        );
    }

    public static function formatSecondsAsHoursMinutes(int $seconds): string
    {
        $splitDuration = self::splitDuration($seconds * 10);
        $hours = u((string) $splitDuration['hours'])->padStart(2, '0')->toString();
        $minutes = u((string) $splitDuration['minutes'])->padStart(2, '0')->toString();

        return \sprintf('%s:%s', $hours, $minutes);
    }

    public static function formatTenthSecondsAsHoursMinutesSecondsAndTenthSeconds(int $tenthSeconds): string
    {
        $splitDuration = self::splitDuration($tenthSeconds);
        $hours = u((string) $splitDuration['hours'])->padStart(2, '0')->toString();
        $minutes = u((string) $splitDuration['minutes'])->padStart(2, '0')->toString();
        $seconds = u((string) $splitDuration['seconds'])->padStart(2, '0')->toString();
        $tenthSeconds = (string) $splitDuration['tenthSeconds'];

        if (0 === $splitDuration['hours']) {
            return \sprintf('%s:%s.%s', $minutes, $seconds, $tenthSeconds);
        }

        return \sprintf('%s:%s:%s.%s', $hours, $minutes, $seconds, $tenthSeconds);
    }

    public static function formatSecondsAsMinutesSeconds(int $seconds): string
    {
        $splitDuration = self::splitDuration($seconds * 10);
        $minutes = u((string) ($splitDuration['hours'] * 60 + $splitDuration['minutes']))->padStart(2, '0')->toString();

        return \sprintf('%s:%s', $minutes, u((string) $splitDuration['seconds'])->padStart(2, '0')->toString());
    }

    private static function splitDuration(int $tenthSeconds): array
    {
        $hours = intdiv($tenthSeconds, 36000);
        $minutes = intdiv($tenthSeconds - $hours * 36000, 600);
        $seconds = intdiv($tenthSeconds - $hours * 36000 - $minutes * 600, 10);
        $tenthSeconds = $tenthSeconds - $hours * 36000 - $minutes * 600 - $seconds * 10;

        return [
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => $seconds,
            'tenthSeconds' => $tenthSeconds,
        ];
    }
}
