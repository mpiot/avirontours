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

use App\Util\MathHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MathHelperTest extends TestCase
{
    /**
     * @param array<int|float> $values
     * @param array<int>       $shares
     */
    #[DataProvider('provideShares')]
    public function testSharesSumToTheTotal(array $values, array $shares, int $total = 100): void
    {
        self::assertSame($shares, MathHelper::shares($values, $total));
    }

    public function testSharesKeepTheirKeys(): void
    {
        self::assertSame(
            ['rowing' => 33, 'running' => 67],
            MathHelper::shares(['rowing' => 1, 'running' => 2])
        );
    }

    public static function provideShares(): \Generator
    {
        yield 'thirds do not divide' => [[10, 10, 10], [34, 33, 33]];
        yield 'a clean split needs no remainder' => [[2, 3], [40, 60]];
        yield 'one value takes everything' => [[42], [100]];
        yield 'a total of zero shares nothing' => [[0, 0], [0, 0]];
        yield 'nothing to share' => [[], []];
        yield 'a total other than a percentage' => [[1, 1, 1], [4, 3, 3], 10];
    }
}
