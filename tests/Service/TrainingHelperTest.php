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
use App\Enum\SportSpecificity;
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

        $specificities = $this->currentWeekSpecificities($user);

        self::assertSame(0, $specificities[0]['share']);
    }

    public function testSummarySharesSumToOneHundred(): void
    {
        $user = UserFactory::createOne();
        $wednesday = new \DateTime('wednesday this week');
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 15, 'trainedAt' => $wednesday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Running, 'duration' => 25, 'trainedAt' => $wednesday]);

        $shares = array_column($this->currentWeekSpecificities($user), 'share');

        self::assertSame(100, array_sum($shares));
        self::assertSame([40, 60], $shares);
    }

    public function testSummaryMergesTheSportsOfOneSpecificityInEnumOrder(): void
    {
        $user = UserFactory::createOne();
        $wednesday = new \DateTime('wednesday this week');
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Cycling, 'duration' => 18000, 'distance' => 20000, 'trainedAt' => $wednesday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Running, 'duration' => 9000, 'distance' => 5000, 'trainedAt' => $wednesday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 9000, 'distance' => 10000, 'trainedAt' => $wednesday]);

        $specificities = $this->currentWeekSpecificities($user);

        self::assertSame([SportSpecificity::Specific, SportSpecificity::NonSpecific], array_column($specificities, 'specificity'));
        self::assertSame(2, $specificities[1]['sessions']);
        self::assertSame(2700, $specificities[1]['duration']);
        self::assertSame(25000, $specificities[1]['distance']);
    }

    public function testSummarySharesSumToOneHundredForThreeThirds(): void
    {
        $user = UserFactory::createOne();
        $wednesday = new \DateTime('wednesday this week');
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Rowing, 'duration' => 100, 'trainedAt' => $wednesday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Ergometer, 'duration' => 100, 'trainedAt' => $wednesday]);
        TrainingFactory::createOne(['user' => $user, 'sport' => SportType::Cycling, 'duration' => 100, 'trainedAt' => $wednesday]);

        $shares = array_column($this->currentWeekSpecificities($user), 'share');

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

    public function testDashboardVolumesOnlyCountTheLastSevenDays(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-1 day'), 'duration' => 36000, 'distance' => 10000]);
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-3 days'), 'duration' => 54000, 'distance' => null]);
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-10 days'), 'duration' => 36000, 'distance' => 10000]);

        $kpis = $this->dashboardKpis($user);

        self::assertTrue($kpis['hasRecentTrainings']);
        self::assertSame(2, $kpis['volumes']['sessions']);
        self::assertSame(9000, $kpis['volumes']['duration']);
        self::assertSame(10000, $kpis['volumes']['distance']);
    }

    public function testDashboardHasNoRecentTrainingsBeyondFourWeeks(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-40 days')]);

        $kpis = $this->dashboardKpis($user);

        self::assertFalse($kpis['hasRecentTrainings']);
        self::assertSame(0, $kpis['volumes']['sessions']);
    }

    public function testDashboardLoadRatioComparesAcuteAndChronicWindows(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-1 day'), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard]);
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-10 days'), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::Easy]);

        $load = $this->dashboardKpis($user)['load'];

        self::assertSame(240, $load['acute']);
        self::assertSame(90.0, $load['chronic']);
        self::assertEqualsWithDelta(2.67, $load['ratio'], 0.01);
        self::assertSame('Charge élevée', $load['zone']);
    }

    public function testDashboardLoadZoneIsUsualWhenAcuteMatchesChronic(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-1 day'), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard]);
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-8 days'), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard]);
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-15 days'), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard]);
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-22 days'), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard]);

        $load = $this->dashboardKpis($user)['load'];

        self::assertSame(240, $load['acute']);
        self::assertSame(1.0, $load['ratio']);
        self::assertSame('Zone habituelle', $load['zone']);
    }

    public function testDashboardLoadRestWeekIsARealZeroNotAnUnknown(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-10 days'), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard]);

        $load = $this->dashboardKpis($user)['load'];

        self::assertSame(0, $load['acute']);
        self::assertSame(0.0, $load['ratio']);
        self::assertSame('Charge allégée', $load['zone']);
    }

    public function testDashboardLoadIsUnknownWhenTheWeekWasTrainedButNeverRated(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-1 day'), 'ratedPerceivedExertion' => null]);
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-10 days'), 'duration' => 36000, 'ratedPerceivedExertion' => RatedPerceivedExertion::SomewhatHard]);

        $load = $this->dashboardKpis($user)['load'];

        self::assertNull($load['acute']);
        self::assertNull($load['ratio']);
        self::assertNull($load['zone']);
    }

    public function testDashboardLoadIsNullWhenNoSessionWasRated(): void
    {
        $user = UserFactory::createOne();
        TrainingFactory::createOne(['user' => $user, 'trainedAt' => new \DateTime('-1 day'), 'ratedPerceivedExertion' => null]);

        $load = $this->dashboardKpis($user)['load'];

        self::assertNull($load['acute']);
        self::assertNull($load['chronic']);
        self::assertNull($load['ratio']);
        self::assertNull($load['zone']);
    }

    /**
     * @return list<array{specificity: SportSpecificity, sessions: int, duration: int, distance: int, share: int}>
     */
    private function currentWeekSpecificities(User $user): array
    {
        return $this->currentWeekSummary($user)['specificities'];
    }

    private function dashboardKpis(User $user): array
    {
        return self::getContainer()->get(TrainingHelper::class)->getDashboardKpis($user);
    }

    private function currentWeekSummary(User $user): array
    {
        $summary = self::getContainer()->get(TrainingHelper::class)->getTrainingsSummary(
            $user,
            new \DateTimeImmutable('monday this week'),
            new \DateTimeImmutable('sunday this week'),
        );

        foreach ($summary as $week) {
            if ([] !== $week['summary']['specificities']) {
                return $week['summary'];
            }
        }

        self::fail('No week with trainings found in the summary.');
    }
}
