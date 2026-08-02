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

namespace App\Tests\Service;

use App\Entity\Shell;
use App\Service\ShellAbbreviationGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ShellAbbreviationGeneratorTest extends TestCase
{
    #[DataProvider('abbreviationProvider')]
    public function testGenerateAbbreviation(int $numberRowers, string $rowingType, bool $coxed, bool $yolette, string $expected): void
    {
        $shell = (new Shell())
            ->setNumberRowers($numberRowers)
            ->setRowingType($rowingType)
            ->setCoxed($coxed)
            ->setYolette($yolette)
        ;

        self::assertSame($expected, (new ShellAbbreviationGenerator())->generateAbbreviation($shell));
    }

    public static function abbreviationProvider(): \Generator
    {
        // [numberRowers, rowingType, coxed, yolette, expected] — the standard rowing boat classes.
        // Sculling:
        yield 'single scull' => [1, Shell::ROWING_TYPE_SCULL, false, false, '1x'];
        yield 'double scull' => [2, Shell::ROWING_TYPE_SCULL, false, false, '2x'];
        yield 'quadruple scull' => [4, Shell::ROWING_TYPE_SCULL, false, false, '4x'];
        yield 'coxed quad' => [4, Shell::ROWING_TYPE_SCULL, true, false, '4x+'];
        yield 'coxed sculling yolette' => [4, Shell::ROWING_TYPE_SCULL, true, true, '4Yx+'];
        yield 'octuple scull' => [8, Shell::ROWING_TYPE_SCULL, true, false, '8x+'];
        // Sweep:
        yield 'coxless pair' => [2, Shell::ROWING_TYPE_SWEEP, false, false, '2-'];
        yield 'coxed pair' => [2, Shell::ROWING_TYPE_SWEEP, true, false, '2+'];
        yield 'coxless four' => [4, Shell::ROWING_TYPE_SWEEP, false, false, '4-'];
        yield 'coxed four' => [4, Shell::ROWING_TYPE_SWEEP, true, false, '4+'];
        yield 'coxed sweep yolette' => [4, Shell::ROWING_TYPE_SWEEP, true, true, '4Y+'];
        yield 'eight' => [8, Shell::ROWING_TYPE_SWEEP, true, false, '8+'];
        // A versatile shell riggable both ways shows both abbreviations (not a competition class).
        yield 'convertible rig' => [4, Shell::ROWING_TYPE_BOTH, false, false, '4x/4-'];
    }
}
