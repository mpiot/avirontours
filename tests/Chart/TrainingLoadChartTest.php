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

use App\Chart\TrainingLoadChart;
use App\Entity\User;
use App\Enum\RatedPerceivedExertion;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TrainingLoadChartTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testChartHasEightWeeklyBarsWithAConditionAndAFatigueLine(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('today'), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard]);

        $series = $this->series($user);

        self::assertSame(['Charge', 'Condition', 'Fatigue'], array_keys($series));
        self::assertCount(8, $series['Charge']);
        // One session of 240 today: it is the week's load and this week's fatigue, and 240/42 × 7 of condition.
        self::assertSame(240, array_last($series['Charge']));
        self::assertSame(40, array_last($series['Condition']));
        self::assertSame(240, array_last($series['Fatigue']));
    }

    public function testWeeksBeforeTheFirstRatedSessionReadZero(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('today'), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard]);

        $series = $this->series($user);

        self::assertSame(0, array_first($series['Charge']));
        self::assertSame(0, array_first($series['Condition']));
        self::assertSame(0, array_first($series['Fatigue']));
    }

    public function testConditionLineSurvivesTwoWeeksOffWhileFatigueLineFades(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createMany(42, static fn (int $i): array => ['user' => $user, 'trainedAt' => new \DateTime(\sprintf('-%d days', 13 + $i)), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard]);

        $series = $this->series($user);

        // Six weeks of daily 240, then two weeks off: the running week still carries 763 of condition
        // against 194 of fatigue, where a 28-day span would have shown the reference collapsing.
        self::assertSame(0, array_last($series['Charge']));
        self::assertSame(763, array_last($series['Condition']));
        self::assertSame(194, array_last($series['Fatigue']));
    }

    public function testNoChartWhenNothingWasRatedInTheEightWeeks(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('monday this week -8 weeks'), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard]);
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('today'), 'ratedPerceivedExertion' => null]);

        self::assertNull(self::getContainer()->get(TrainingLoadChart::class)->weekly($user));
    }

    /**
     * @return array<string, list<int>> the eight weekly points of each dataset, keyed by its label
     */
    private function series(User $user): array
    {
        $datasets = self::getContainer()->get(TrainingLoadChart::class)->weekly($user)->getData()['datasets'];

        return array_column($datasets, 'data', 'label');
    }
}
