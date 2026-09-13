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

namespace App\Tests\Service\Fit;

use App\Enum\TrainingPhaseIntensity;
use App\Service\Fit\FitDeviceQuirks;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sportlog\FIT\Profile\Types\Manufacturer;

class FitDeviceQuirksTest extends TestCase
{
    /**
     * @param list<TrainingPhaseIntensity> $intensities
     */
    #[DataProvider('provideLaps')]
    public function testOnlyAllRestConcept2LapsAreSplits(?int $manufacturer, array $intensities, bool $expected): void
    {
        self::assertSame($expected, FitDeviceQuirks::lapsAreSplits($manufacturer, $intensities));
    }

    /**
     * @return iterable<string, array{?int, list<TrainingPhaseIntensity>, bool}>
     */
    public static function provideLaps(): iterable
    {
        $rest = TrainingPhaseIntensity::Rest;
        $active = TrainingPhaseIntensity::Active;

        yield 'Concept2 continuous piece: every split is REST' => [Manufacturer::CONCEPT2, [$rest, $rest, $rest], true];
        yield 'Concept2 interval workout: work and rest' => [Manufacturer::CONCEPT2, [$active, $rest, $active], false];
        yield 'Concept2 without a lap' => [Manufacturer::CONCEPT2, [], false];
        yield 'Polar: a rest-only file is what it says' => [Manufacturer::POLAR_ELECTRO, [$rest, $rest], false];
        yield 'Unknown manufacturer' => [null, [$rest, $rest], false];
    }
}
