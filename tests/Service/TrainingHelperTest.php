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
use App\Enum\RatedPerceivedExertion;
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

    public function testSummaryShareIsZeroForNegligibleDuration(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne([
            'user' => $user,
            'sport' => SportType::Rowing,
            'duration' => 3,
            'trainedAt' => new \DateTime('wednesday this week'),
        ]);

        $sports = $this->currentWeekSports($user);

        self::assertSame(0, $sports[0]['share']);
    }

    public function testSummarySharesSumToOneHundred(): void
    {
        $user = UserFactory::createOne();
        $wednesday = new \DateTime('wednesday this week');
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 15, 'trainedAt' => $wednesday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Running, 'duration' => 25, 'trainedAt' => $wednesday]);

        $shares = array_column($this->currentWeekSports($user), 'share');

        self::assertSame(100, array_sum($shares));
        self::assertSame([40, 60], $shares);
    }

    public function testSummarySharesSumToOneHundredForThreeThirds(): void
    {
        $user = UserFactory::createOne();
        $wednesday = new \DateTime('wednesday this week');
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 100, 'trainedAt' => $wednesday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Running, 'duration' => 100, 'trainedAt' => $wednesday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Cycling, 'duration' => 100, 'trainedAt' => $wednesday]);

        $shares = array_column($this->currentWeekSports($user), 'share');

        self::assertSame(100, array_sum($shares));
        self::assertSame([34, 33, 33], $shares);
    }

    public function testSummaryLoadOnlyCountsRatedSessions(): void
    {
        $user = UserFactory::createOne();
        $wednesday = new \DateTime('wednesday this week');
        TrainingFactory::createOne(['user' => $user, 'duration' => 18000, 'ratedPerceivedExertion' => RatedPerceivedExertion::ReallyHard, 'trainedAt' => $wednesday]);
        TrainingFactory::createOne(['user' => $user, 'duration' => 9000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard, 'trainedAt' => $wednesday]);
        TrainingFactory::createOne(['user' => $user, 'duration' => 9000, 'ratedPerceivedExertion' => null, 'trainedAt' => $wednesday]);

        $summary = $this->currentWeekSummary($user);

        // 6 × 30 min and 4 × 15 min, plus one session nobody rated.
        self::assertSame(240, $summary['load']);
        self::assertSame(3, $summary['sessions']);
    }

    public function testSummaryLoadIsNullWhenNoSessionWasRated(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne([
            'user' => $user,
            'ratedPerceivedExertion' => null,
            'trainedAt' => new \DateTime('wednesday this week'),
        ]);

        $summary = $this->currentWeekSummary($user);

        self::assertNull($summary['load']);
        self::assertSame(1, $summary['sessions']);
    }

    /**
     * @return list<array{sport: SportType, sessions: int, duration: int, distance: int, share: int}>
     */
    private function currentWeekSports(User $user): array
    {
        return $this->currentWeekSummary($user)['sports'];
    }

    private function currentWeekSummary(User $user): array
    {
        $summary = self::getContainer()->get(TrainingHelper::class)->getTrainingsSummary(
            $user,
            new \DateTimeImmutable('monday this week'),
            new \DateTimeImmutable('sunday this week'),
        );

        foreach ($summary as $week) {
            if ([] !== $week['summary']['sports']) {
                return $week['summary'];
            }
        }

        self::fail('No week with trainings found in the summary.');
    }
}
