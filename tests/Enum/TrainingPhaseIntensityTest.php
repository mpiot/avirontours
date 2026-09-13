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

use App\Enum\TrainingPhaseIntensity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sportlog\FIT\Profile\Types\Intensity;

class TrainingPhaseIntensityTest extends TestCase
{
    #[DataProvider('provideFitIntensities')]
    public function testEachFitLapIntensityLandsOnAPhaseIntensity(?int $intensity, TrainingPhaseIntensity $expected): void
    {
        self::assertSame($expected, TrainingPhaseIntensity::fromFit($intensity));
    }

    public static function provideFitIntensities(): iterable
    {
        yield 'active' => [Intensity::ACTIVE, TrainingPhaseIntensity::Active];
        yield 'rest' => [Intensity::REST, TrainingPhaseIntensity::Rest];
        yield 'warmup' => [Intensity::WARMUP, TrainingPhaseIntensity::WarmUp];
        yield 'cooldown' => [Intensity::COOLDOWN, TrainingPhaseIntensity::CoolDown];
        yield 'recovery' => [Intensity::RECOVERY, TrainingPhaseIntensity::Rest];
        yield 'interval' => [Intensity::INTERVAL, TrainingPhaseIntensity::Active];
        yield 'other' => [Intensity::OTHER, TrainingPhaseIntensity::Active];
        yield 'not written (Polar, NK)' => [null, TrainingPhaseIntensity::Active];
    }
}
