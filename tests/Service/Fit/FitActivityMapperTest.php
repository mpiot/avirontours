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

use App\Entity\User;
use App\Enum\SportType;
use App\Enum\TrainingPhaseIntensity;
use App\Enum\TrainingSource;
use App\Service\Fit\Exception\UnsupportedFitFileException;
use App\Service\Fit\FitActivityMapper;
use App\Service\Fit\Model\FitActivity;
use App\Service\Fit\Model\FitRecord;
use App\Service\Fit\Model\FitSegment;
use PHPUnit\Framework\TestCase;
use Sportlog\FIT\Profile\Types\Sport;
use Sportlog\FIT\Profile\Types\SubSport;

class FitActivityMapperTest extends TestCase
{
    private const string START = '2026-08-29 08:00:00 UTC';

    private string $timeZone;

    public function testTheTrainingCarriesTheSessionFigures(): void
    {
        $activity = $this->activity([
            'device' => 'Garmin FR255',
            'sport' => Sport::ROWING,
            'subSport' => SubSport::GENERIC,
            'totalTimerTime' => 4452.0,
            'totalElapsedTime' => 4513.0,
            'totalDistance' => 10041.1,
            'avgHeartRate' => 148,
            'maxHeartRate' => 186,
            'avgCadence' => 22,
            'avgPower' => 180,
        ]);

        $training = (new FitActivityMapper())->createTraining(new User(), $activity);

        self::assertSame(TrainingSource::Fit, $training->getSource());
        self::assertSame('Garmin FR255', $training->getDevice());
        self::assertSame(SportType::Rowing, $training->getSport());
        self::assertSame(44520, $training->getDuration());
        self::assertSame(10041, $training->getDistance());
        self::assertSame(148, $training->getAverageHeartRate());
        self::assertSame(186, $training->getMaxHeartRate());
        self::assertSame(22, $training->getStrokeRate());
        self::assertSame(180, $training->getAveragePower());
        self::assertNull($training->getFeeling());
        self::assertNull($training->getRatedPerceivedExertion());
        self::assertNull($training->getComment());
    }

    public function testTrainedAtIsTheWallClockWrittenByTheDevice(): void
    {
        $activity = $this->activity(['localStartedAt' => new \DateTimeImmutable('2026-08-29 10:00:00')]);

        $training = (new FitActivityMapper())->createTraining(new User(), $activity);

        self::assertSame('2026-08-29 10:00:00', $training->getTrainedAt()->format('Y-m-d H:i:s'));
    }

    public function testTrainedAtFallsBackToTheStartInTheApplicationTimeZone(): void
    {
        $training = (new FitActivityMapper())->createTraining(new User(), $this->activity());

        // START is 08:00 UTC and the application runs on Europe/Paris, pinned in setUp().
        self::assertSame('2026-08-29 10:00:00', $training->getTrainedAt()->format('Y-m-d H:i:s'));
    }

    public function testDurationFallsBackToTheElapsedTimeThenToTheRecords(): void
    {
        $fromElapsed = (new FitActivityMapper())->createTraining(new User(), $this->activity(['totalTimerTime' => null, 'totalElapsedTime' => 90.5]));
        self::assertSame(905, $fromElapsed->getDuration());

        $fromRecords = (new FitActivityMapper())->createTraining(new User(), $this->activity(
            ['totalTimerTime' => null, 'totalElapsedTime' => null],
            records: [$this->record(10), $this->record(120)],
        ));
        self::assertSame(1200, $fromRecords->getDuration());
    }

    public function testASessionWithoutDurationIsUnsupported(): void
    {
        $this->expectException(UnsupportedFitFileException::class);

        (new FitActivityMapper())->createTraining(new User(), $this->activity(['totalTimerTime' => null, 'totalElapsedTime' => null]));
    }

    public function testAZeroDistanceIsNoDistance(): void
    {
        $training = (new FitActivityMapper())->createTraining(new User(), $this->activity(['totalDistance' => 0.0]));

        self::assertNull($training->getDistance());
    }

    public function testWithoutLapsTheSessionIsTheOnlyPhase(): void
    {
        $activity = $this->activity(
            ['totalTimerTime' => 600.0, 'totalDistance' => 2500.0, 'avgHeartRate' => 150],
            records: [$this->record(1, heartRate: 140), $this->record(2, heartRate: 160)],
        );

        $training = (new FitActivityMapper())->createTraining(new User(), $activity);

        self::assertCount(1, $training->getTrainingPhases());
        $phase = $training->getTrainingPhases()->first();
        self::assertSame(6000, $phase->getDuration());
        self::assertSame(2500, $phase->getDistance());
        self::assertSame(TrainingPhaseIntensity::Active, $phase->getIntensity());
        self::assertSame(150, $phase->getAverageHeartRate());
        self::assertSame([10, 20], $phase->getTimes());
        self::assertSame([140, 160], $phase->getHeartRates());
    }

    public function testARestLapIsARestPhaseWithItsOwnRecords(): void
    {
        $activity = $this->activity(
            laps: [$this->lap(0, 100), $this->lap(100, 160, intensity: TrainingPhaseIntensity::Rest, distance: 0.0), $this->lap(160, 260)],
            records: [$this->record(50, heartRate: 170), $this->record(130, heartRate: 120, speed: 0.0), $this->record(200, heartRate: 165)],
        );

        $phases = (new FitActivityMapper())->createTraining(new User(), $activity)->getTrainingPhases();

        self::assertSame(TrainingPhaseIntensity::Rest, $phases[1]->getIntensity());
        self::assertTrue($phases[1]->isRest());
        self::assertSame(0, $phases[1]->getDistance());
        self::assertNull($phases[1]->getPace());
        self::assertSame([120], $phases[1]->getHeartRates());
        self::assertSame(120, $phases[1]->getAverageHeartRate());
    }

    public function testALapWithoutRecordsKeepsItsFiguresAndNoSeries(): void
    {
        $activity = $this->activity(laps: [$this->lap(0, 100, heartRate: 150, cadence: 24, power: 200)]);

        $phase = (new FitActivityMapper())->createTraining(new User(), $activity)->getTrainingPhases()->first();

        self::assertSame(1000, $phase->getDuration());
        self::assertSame(150, $phase->getAverageHeartRate());
        self::assertSame(24, $phase->getStrokeRate());
        self::assertSame(200, $phase->getAveragePower());
        self::assertNull($phase->getTimes());
        self::assertNull($phase->getHeartRates());
        self::assertNull($phase->getEndingHeartRate());
    }

    public function testTimesFollowTheRunningTimerNotTheWallClock(): void
    {
        $activity = $this->activity(
            ['totalTimerTime' => 240.0],
            laps: [$this->lap(0, 300, timer: 240.0)],
            records: [$this->record(50, timerAt: 50), $this->record(200, timerAt: 140), $this->record(300, timerAt: 240)],
        );

        $phase = (new FitActivityMapper())->createTraining(new User(), $activity)->getTrainingPhases()->first();

        self::assertSame([500, 1400, 2400], $phase->getTimes());
    }

    public function testPacesComeFromTheSpeedAndAStoppedRecordIsCapped(): void
    {
        $rowing = (new FitActivityMapper())->createTraining(new User(), $this->activity(
            ['sport' => Sport::ROWING],
            records: [$this->record(1, speed: 4.0), $this->record(2, speed: 0.0), $this->record(3, speed: 1.0)],
        ));
        self::assertSame([1250, 2400, 2400], $rowing->getTrainingPhases()->first()->getPaces());

        $running = (new FitActivityMapper())->createTraining(new User(), $this->activity(
            ['sport' => Sport::RUNNING],
            records: [$this->record(1, speed: 3.0), $this->record(2, speed: 0.0)],
        ));
        self::assertSame([1667, 36000], $running->getTrainingPhases()->first()->getPaces());
    }

    public function testRunningCadenceIsReadAsSteps(): void
    {
        $running = (new FitActivityMapper())->createTraining(new User(), $this->activity(
            ['sport' => Sport::RUNNING, 'avgCadence' => 88],
            laps: [$this->lap(0, 100, cadence: 88)],
            records: [$this->record(1, cadence: 88.5), $this->record(2, cadence: 90.0)],
        ));
        self::assertSame([177, 180], $running->getTrainingPhases()->first()->getStrokeRates());
        self::assertSame(176, $running->getStrokeRate());
        self::assertSame(176, $running->getTrainingPhases()->first()->getStrokeRate());

        $rowing = (new FitActivityMapper())->createTraining(new User(), $this->activity(
            ['sport' => Sport::ROWING],
            records: [$this->record(1, cadence: 24.4)],
        ));
        self::assertSame([24], $rowing->getTrainingPhases()->first()->getStrokeRates());
    }

    public function testAnOutOfWindowSampleRepeatsThePreviousOne(): void
    {
        $activity = $this->activity(
            ['sport' => Sport::ROWING],
            records: [
                $this->record(1, heartRate: 20, cadence: 90.0),
                $this->record(2, heartRate: 125, cadence: 24.0),
                $this->record(3, heartRate: 255, cadence: 0.0),
                $this->record(4, heartRate: 130, cadence: 26.0),
            ],
        );

        $phase = (new FitActivityMapper())->createTraining(new User(), $activity)->getTrainingPhases()->first();

        self::assertSame([125, 125, 125, 130], $phase->getHeartRates());
        self::assertSame([24, 24, 24, 26], $phase->getStrokeRates());
        self::assertSame(128, $phase->getAverageHeartRate());
        self::assertSame(130, $phase->getMaxHeartRate());
        self::assertSame(25, $phase->getStrokeRate());
    }

    public function testASeriesIsNullWhenNoRecordCarriesTheQuantity(): void
    {
        $activity = $this->activity(records: [$this->record(1, heartRate: 120), $this->record(2, heartRate: 125)]);

        $phase = (new FitActivityMapper())->createTraining(new User(), $activity)->getTrainingPhases()->first();

        self::assertSame([10, 20], $phase->getTimes());
        self::assertSame([120, 125], $phase->getHeartRates());
        self::assertNull($phase->getPaces());
        self::assertNull($phase->getStrokeRates());
        self::assertNull($phase->getPowers());
        self::assertNull($phase->getDistances());
        self::assertNull($phase->getPositionTimes());
        self::assertNull($phase->getStrokeRate());
        self::assertNull($phase->getAveragePower());
    }

    public function testAggregatesPreferTheFitFigureAndFallBackToTheRecords(): void
    {
        $activity = $this->activity(
            ['avgHeartRate' => 0, 'maxHeartRate' => null, 'avgPower' => 0, 'avgCadence' => null],
            laps: [$this->lap(0, 100, heartRate: 140, power: 0)],
            records: [$this->record(1, heartRate: 100, power: 150, cadence: 20.0), $this->record(2, heartRate: 120, power: 170, cadence: 22.0)],
        );

        $training = (new FitActivityMapper())->createTraining(new User(), $activity);
        $phase = $training->getTrainingPhases()->first();

        self::assertSame(140, $phase->getAverageHeartRate());
        self::assertSame(120, $phase->getMaxHeartRate());
        self::assertSame(160, $phase->getAveragePower());
        self::assertSame(120, $phase->getEndingHeartRate());
        self::assertSame(110, $training->getAverageHeartRate());
        self::assertSame(120, $training->getMaxHeartRate());
        self::assertSame(160, $training->getAveragePower());
        self::assertSame(21, $training->getStrokeRate());
    }

    public function testAPowerOfZeroIsNoPower(): void
    {
        $activity = $this->activity(
            ['avgPower' => 0],
            laps: [$this->lap(0, 100, power: 0)],
            records: [$this->record(1, power: 0), $this->record(2, power: 0)],
        );

        $training = (new FitActivityMapper())->createTraining(new User(), $activity);

        self::assertNull($training->getAveragePower());
        self::assertNull($training->getTrainingPhases()->first()->getAveragePower());
        self::assertSame([0, 0], $training->getTrainingPhases()->first()->getPowers());
    }

    public function testDistancesAreRelativeToTheLap(): void
    {
        $activity = $this->activity(
            laps: [$this->lap(0, 100), $this->lap(100, 200)],
            records: [$this->record(10, distance: 1000.4), $this->record(20, distance: 1010.0), $this->record(30), $this->record(40, distance: 1030.0), $this->record(150, distance: 2000.0)],
        );

        $phases = (new FitActivityMapper())->createTraining(new User(), $activity)->getTrainingPhases();

        self::assertSame([0, 10, 10, 30], $phases[0]->getDistances());
        self::assertSame([0], $phases[1]->getDistances());
    }

    public function testTheGpsTrackKeepsOnlyTheRecordsWithAFix(): void
    {
        $activity = $this->activity(records: [
            $this->record(10, latitude: 565180035, longitude: 8679850),
            $this->record(20, latitude: 565180100, longitude: 8679900, altitude: 52.4),
            $this->record(30),
            $this->record(40, latitude: 565180200),
            $this->record(50, latitude: 565180300, longitude: 8680000, altitude: 53.6),
        ]);

        $phase = (new FitActivityMapper())->createTraining(new User(), $activity)->getTrainingPhases()->first();

        self::assertSame([100, 200, 500], $phase->getPositionTimes());
        self::assertSame([565180035, 565180100, 565180300], $phase->getLatitudes());
        self::assertSame([8679850, 8679900, 8680000], $phase->getLongitudes());
        self::assertSame([52, 52, 54], $phase->getAltitudes());
    }

    public function testAltitudesAreNullWhenTheDeviceHasNone(): void
    {
        $activity = $this->activity(records: [$this->record(10, latitude: 1, longitude: 2)]);

        $phase = (new FitActivityMapper())->createTraining(new User(), $activity)->getTrainingPhases()->first();

        self::assertSame([100], $phase->getPositionTimes());
        self::assertNull($phase->getAltitudes());
    }

    protected function setUp(): void
    {
        // The mapper reads the application time zone; php.ini only sets it for the Symfony CLI.
        $this->timeZone = date_default_timezone_get();
        date_default_timezone_set('Europe/Paris');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->timeZone);
    }

    /**
     * @param array<string, mixed> $session
     * @param list<FitSegment>     $laps
     * @param list<FitRecord>      $records
     */
    private function activity(array $session = [], array $laps = [], array $records = []): FitActivity
    {
        $session += [
            'device' => null,
            'sport' => Sport::ROWING,
            'subSport' => SubSport::GENERIC,
            'localStartedAt' => null,
            'totalTimerTime' => 300.0,
            'totalElapsedTime' => 300.0,
            'totalDistance' => 1000.0,
            'avgHeartRate' => null,
            'maxHeartRate' => null,
            'avgCadence' => null,
            'avgPower' => null,
        ];

        // Assembled the way the reader does it: each lap takes the samples inside its window, the session holds what they hold.
        foreach ($laps as $index => $lap) {
            $laps[$index] = $lap->withRecords(array_values(array_filter($records, static fn (FitRecord $record): bool => $lap->startedAt <= $record->recordedAt && $record->recordedAt <= $lap->endedAt)));
        }
        $sessionSegment = new FitSegment(
            startedAt: $this->at(0),
            endedAt: $this->at(300),
            timerAt: 0,
            totalTimerTime: $session['totalTimerTime'],
            totalElapsedTime: $session['totalElapsedTime'],
            totalDistance: $session['totalDistance'],
            avgHeartRate: $session['avgHeartRate'],
            maxHeartRate: $session['maxHeartRate'],
            avgCadence: $session['avgCadence'],
            avgPower: $session['avgPower'],
            records: [] === $laps ? $records : array_merge(...array_map(static fn (FitSegment $lap): array => $lap->records, $laps)),
        );

        return new FitActivity(
            device: $session['device'],
            sport: $session['sport'],
            subSport: $session['subSport'],
            startedAt: new \DateTimeImmutable(self::START),
            localStartedAt: $session['localStartedAt'],
            session: $sessionSegment,
            laps: [] === $laps ? [$sessionSegment] : $laps,
        );
    }

    private function lap(int $from, int $to, ?float $timer = null, ?float $distance = 500.0, TrainingPhaseIntensity $intensity = TrainingPhaseIntensity::Active, ?int $heartRate = null, ?int $cadence = null, ?int $power = null): FitSegment
    {
        return new FitSegment(
            startedAt: $this->at($from),
            endedAt: $this->at($to),
            timerAt: $from,
            totalTimerTime: $timer ?? (float) ($to - $from),
            totalElapsedTime: (float) ($to - $from),
            totalDistance: $distance,
            intensity: $intensity,
            avgHeartRate: $heartRate,
            avgCadence: $cadence,
            avgPower: $power,
        );
    }

    private function record(int $offset, ?int $timerAt = null, ?int $latitude = null, ?int $longitude = null, ?float $altitude = null, ?float $distance = null, ?float $speed = null, ?int $heartRate = null, ?float $cadence = null, ?int $power = null): FitRecord
    {
        return new FitRecord($this->at($offset), $timerAt ?? $offset, $latitude, $longitude, $altitude, $distance, $speed, $heartRate, $cadence, $power);
    }

    private function at(int $offset): \DateTimeImmutable
    {
        return (new \DateTimeImmutable(self::START))->modify(\sprintf('%+d seconds', $offset));
    }
}
