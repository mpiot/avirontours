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

use App\Util\WattCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WattCalculatorTest extends TestCase
{
    #[DataProvider('providePaces')]
    public function testCalculateFromPace(int $pace, int $expectedWatt): void
    {
        self::assertSame($expectedWatt, WattCalculator::calculateFromPace($pace));
    }

    public static function providePaces(): \Generator
    {
        yield 'a 2:00.0 pace is the 203 W reference' => [1200, 203];
        yield 'a 1:54.3 pace rounds down to 234 W' => [1143, 234];
        yield 'a slow 3:00.0 pace rounds to 60 W' => [1800, 60];
    }
}
