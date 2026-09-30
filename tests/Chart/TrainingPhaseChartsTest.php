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

namespace App\Tests\Chart;

use App\Chart\TrainingPhaseCharts;
use App\Factory\TrainingPhaseFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TrainingPhaseChartsTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testUnmeasuredValuesAreLeftOutOfTheTraces(): void
    {
        $trainingPhase = TrainingPhaseFactory::createOne([
            'times' => [7, 30, 52],
            'paces' => [0, 1398, 1133],
            'strokeRates' => [0, 0, 28],
        ]);

        $charts = self::getContainer()->get(TrainingPhaseCharts::class)->charts($trainingPhase);

        self::assertSame([null, 140, 113], $charts[0]['chart']->getData()['datasets'][0]['data']);
        self::assertSame([null, null, 28], $charts[1]['chart']->getData()['datasets'][0]['data']);
    }
}
