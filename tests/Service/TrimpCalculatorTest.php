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

use App\Entity\Physiology;
use App\Entity\Training;
use App\Entity\TrainingPhase;
use App\Entity\User;
use App\Service\TrimpCalculator;
use PHPUnit\Framework\TestCase;

class TrimpCalculatorTest extends TestCase
{
    public function testSixtyMinutesAtSeventyPercentOfTheReserveWeighsAHundredAndThree(): void
    {
        // x = 0.7 constant: 60 min × 0.7 × 0.64 × e^(1.92 × 0.7) ≈ 103 for a man.
        $training = $this->training(gender: 'm', maximumHeartRate: 190, heartRate: 151);

        self::assertSame(103, $this->calculator()->compute($training));
    }

    public function testTheFemaleCoefficientsApply(): void
    {
        // 60 min × 0.7 × 0.86 × e^(1.67 × 0.7) ≈ 116.
        $training = $this->training(gender: 'f', maximumHeartRate: 190, heartRate: 151);

        self::assertSame(116, $this->calculator()->compute($training));
    }

    public function testWithoutAGenderThereIsNoTrimp(): void
    {
        // k is calibrated per gender: with none declared there is nothing to weigh with.
        $training = $this->training(gender: null, maximumHeartRate: 190, heartRate: 151);

        self::assertNull($this->calculator()->compute($training));
    }

    public function testWithoutAMaximumHeartRateThereIsNoTrimp(): void
    {
        $training = $this->training(gender: 'm', maximumHeartRate: null, heartRate: 151);

        self::assertNull($this->calculator()->compute($training));
    }

    public function testTheRestingHeartRateShapesTheReserve(): void
    {
        // Resting HR 74 instead of 60: x = (151 - 74) / (190 - 74) ≈ 0.664 → ≈ 91.
        $training = $this->training(gender: 'm', maximumHeartRate: 190, heartRate: 151, restingHeartRate: 74);

        self::assertSame(91, $this->calculator()->compute($training));
    }

    public function testAHeartRateUnderTheRestingOneWeighsNothing(): void
    {
        $training = $this->training(gender: 'm', maximumHeartRate: 190, heartRate: 50);

        self::assertSame(0, $this->calculator()->compute($training));
    }

    public function testTheSeriesIntegralMatchesTheConstantCaseWhateverTheSampling(): void
    {
        // Per-stroke sampling (irregular steps) at a constant heart rate equals the average form.
        $training = $this->training(gender: 'm', maximumHeartRate: 190, heartRate: null);
        $training->addTrainingPhase($this->phase([0, 30, 70, 36000], [151, 151, 151, 151]));

        self::assertSame(103, $this->calculator()->compute($training));
    }

    public function testTheSeriesWinsOverTheAverage(): void
    {
        // A hard half and an easy half do not weigh like their average: the exponential says more.
        $training = $this->training(gender: 'm', maximumHeartRate: 190, heartRate: 151);
        $training->addTrainingPhase($this->phase([0, 18000, 36000], [125, 177, 177]));

        $trimp = $this->calculator()->compute($training);

        self::assertNotNull($trimp);
        self::assertGreaterThan(103, $trimp);
    }

    public function testTwoPhasesAddUp(): void
    {
        $training = $this->training(gender: 'm', maximumHeartRate: 190, heartRate: null);
        $training->addTrainingPhase($this->phase([0, 18000], [151, 151]));
        $training->addTrainingPhase($this->phase([0, 18000], [151, 151]));

        self::assertSame(103, $this->calculator()->compute($training));
    }

    public function testAPhaseWithoutASeriesStillWeighsItsAverage(): void
    {
        // The chest strap dropped out over the second half: its average is all that is left to count.
        $training = $this->training(gender: 'm', maximumHeartRate: 190, heartRate: null);
        $training->addTrainingPhase($this->phase([0, 18000], [151, 151]));
        $training->addTrainingPhase($this->summarisedPhase(18000, 151));

        self::assertSame(103, $this->calculator()->compute($training));
    }

    public function testAPhaseThatMeasuredNothingWeighsNothing(): void
    {
        $training = $this->training(gender: 'm', maximumHeartRate: 190, heartRate: null);
        $training->addTrainingPhase($this->phase([0, 18000], [151, 151]));
        $training->addTrainingPhase($this->summarisedPhase(18000, null));

        self::assertSame(52, $this->calculator()->compute($training));
    }

    public function testWithoutAnyHeartRateThereIsNoTrimp(): void
    {
        $training = $this->training(gender: 'm', maximumHeartRate: 190, heartRate: null);

        self::assertNull($this->calculator()->compute($training));
    }

    private function calculator(): TrimpCalculator
    {
        return new TrimpCalculator();
    }

    private function training(?string $gender, ?int $maximumHeartRate, ?int $heartRate, int $restingHeartRate = 60): Training
    {
        $user = new User();
        if (null !== $gender) {
            $user->setGender($gender);
        }
        if (null !== $maximumHeartRate) {
            (new Physiology($user))
                ->setMaximumHeartRate($maximumHeartRate)
                ->setRestingHeartRate($restingHeartRate)
            ;
        }

        $training = new Training($user);
        $training
            ->setTrainedAt(new \DateTime('2026-08-29 10:00:00'))
            ->setDuration(36000)
            ->setAverageHeartRate($heartRate)
        ;

        return $training;
    }

    /**
     * A stretch the device summarised without keeping its samples.
     */
    private function summarisedPhase(int $duration, ?int $averageHeartRate): TrainingPhase
    {
        $phase = new TrainingPhase();
        $phase
            ->setDuration($duration)
            ->setDistance(1000)
            ->setAverageHeartRate($averageHeartRate)
        ;

        return $phase;
    }

    /**
     * @param list<int> $times
     * @param list<int> $heartRates
     */
    private function phase(array $times, array $heartRates): TrainingPhase
    {
        $phase = new TrainingPhase();
        $phase
            ->setDuration((int) end($times))
            ->setDistance(1000)
            ->setTimes($times)
            ->setHeartRates($heartRates)
        ;

        return $phase;
    }
}
