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

namespace App\Tests\Enum;

use App\Enum\DistanceUnit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DistanceUnitTest extends TestCase
{
    #[DataProvider('provideUnits')]
    public function testStoredMetersFormatInTheUnit(DistanceUnit $unit, string $formatted): void
    {
        self::assertSame($formatted, $unit->format(16300));
    }

    public function testEveryUnitHasItsFormatTested(): void
    {
        self::assertCount(\count(DistanceUnit::cases()), iterator_to_array(self::provideUnits()));
    }

    public static function provideUnits(): iterable
    {
        yield 'kilometers' => [DistanceUnit::Kilometers, '16,3'];
        yield 'meters' => [DistanceUnit::Meters, '16 300'];
    }
}
