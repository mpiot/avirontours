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

namespace App\Tests\Form\DataTransformer;

use App\Form\DataTransformer\DurationToTenthSecondsTransformer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DurationToTenthSecondsTransformerTest extends TestCase
{
    #[DataProvider('provideDurations')]
    public function testItReadsMinutesAsTenthSeconds(?int $minutes, ?int $tenthSeconds): void
    {
        self::assertSame($tenthSeconds, (new DurationToTenthSecondsTransformer())->reverseTransform($minutes));
    }

    #[DataProvider('provideDurations')]
    public function testItWritesTenthSecondsAsMinutes(?int $minutes, ?int $tenthSeconds): void
    {
        self::assertSame($minutes, (new DurationToTenthSecondsTransformer())->transform($tenthSeconds));
    }

    public function testItWritesWholeMinutesOnly(): void
    {
        self::assertSame(90, (new DurationToTenthSecondsTransformer())->transform(54334));
    }

    public static function provideDurations(): \Generator
    {
        yield 'under the hour' => [45, 27000];
        yield 'an hour and a half' => [90, 54000];
        yield 'a long outing' => [300, 180000];
        yield 'no duration at all' => [null, null];
    }
}
