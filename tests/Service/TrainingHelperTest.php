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

use App\Entity\User;
use App\Enum\SportType;
use App\Factory\TrainingFactory;
use App\Factory\UserFactory;
use App\Service\TrainingHelper;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TrainingHelperTest extends KernelTestCase
{
    use Factories;
    use ResetDatabase;

    public function testSummaryRatioIsZeroForNegligibleDuration(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne([
            'user' => $user,
            'sport' => SportType::Rowing,
            'duration' => 3,
            'trainedAt' => new \DateTime('wednesday this week'),
        ]);

        $sports = $this->currentWeekSports($user);

        self::assertSame(0.0, $sports[0]['ratio']);
    }

    public function testSummaryRatiosSumToOne(): void
    {
        $user = UserFactory::createOne();
        $wednesday = new \DateTime('wednesday this week');
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 15, 'trainedAt' => $wednesday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Running, 'duration' => 25, 'trainedAt' => $wednesday]);

        $ratios = array_column($this->currentWeekSports($user), 'ratio');

        self::assertEqualsWithDelta(1.0, array_sum($ratios), 0.001);
        self::assertContains(0.4, $ratios);
        self::assertContains(0.6, $ratios);
    }

    /**
     * @return list<array{sport: SportType, sessions: int, duration: int, distance: int, ratio: float}>
     */
    private function currentWeekSports(User $user): array
    {
        $summary = self::getContainer()->get(TrainingHelper::class)->getTrainingsSummary(
            $user,
            new \DateTimeImmutable('monday this week'),
            new \DateTimeImmutable('sunday this week'),
        );

        foreach ($summary as $week) {
            if ([] !== $week['summary']['sports']) {
                return $week['summary']['sports'];
            }
        }

        self::fail('No week with trainings found in the summary.');
    }
}
