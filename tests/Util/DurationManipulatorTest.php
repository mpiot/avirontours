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

namespace App\Tests\Util;

use App\Util\DurationManipulator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DurationManipulatorTest extends TestCase
{
    #[DataProvider('provideDateIntervals')]
    public function testDateIntervalToTenthSeconds(\DateInterval $dateInterval, int $tenthSeconds): void
    {
        self::assertSame($tenthSeconds, DurationManipulator::dateIntervalToTenthSeconds($dateInterval));
    }

    public function testDateIntervalToTenthSecondsOfNothingIsNull(): void
    {
        self::assertNull(DurationManipulator::dateIntervalToTenthSeconds(null));
    }

    #[DataProvider('provideTenthSeconds')]
    public function testTenthSecondsToDateInterval(int $tenthSeconds, string $dateInterval): void
    {
        self::assertSame($dateInterval, DurationManipulator::tenthSecondsToDateInterval($tenthSeconds)->format('%H:%I:%S.%F'));
    }

    public function testTenthSecondsToDateIntervalOfNothingIsNull(): void
    {
        self::assertNull(DurationManipulator::tenthSecondsToDateInterval(null));
    }

    public static function provideDateIntervals(): iterable
    {
        yield 'zero' => [new \DateInterval('PT0S'), 0];
        yield 'seconds only' => [new \DateInterval('PT30S'), 300];
        yield 'hours minutes seconds' => [new \DateInterval('PT1H2M30S'), 37500];
        yield 'diff with tenths' => [(new \DateTimeImmutable('00:00'))->diff(new \DateTimeImmutable('01:30:33.4')), 54334];
    }

    public static function provideTenthSeconds(): iterable
    {
        yield 'zero' => [0, '00:00:00.000000'];
        yield 'seconds only' => [300, '00:00:30.000000'];
        yield 'hours minutes seconds' => [37500, '01:02:30.000000'];
        yield 'tenths are kept' => [54334, '01:30:33.400000'];
    }
}
