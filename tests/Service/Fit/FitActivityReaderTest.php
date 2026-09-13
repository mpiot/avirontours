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
use App\Service\Fit\Exception\InvalidFitFileException;
use App\Service\Fit\FitActivityReader;
use PHPUnit\Framework\TestCase;
use Sportlog\FIT\Profile\Types\Sport;
use Sportlog\FIT\Profile\Types\SubSport;
use Symfony\Component\HttpFoundation\File\File;

class FitActivityReaderTest extends TestCase
{
    private const string FIXTURES = __DIR__.'/../../../src/DataFixtures/Files/fit';

    public function testReadsAPolarOutdoorRowingSession(): void
    {
        $activity = (new FitActivityReader())->read(new File(self::FIXTURES.'/polar-rowing.fit'));

        self::assertSame('Polar Vantage V', $activity->device);
        self::assertSame(Sport::ROWING, $activity->sport);
        self::assertSame(SubSport::GENERIC, $activity->subSport);
        self::assertSame('2026-08-29T08:07:57+00:00', $activity->startedAt->setTimezone(new \DateTimeZone('UTC'))->format(\DATE_ATOM));
        self::assertNull($activity->localStartedAt);
        self::assertEqualsWithDelta(4452.0, $activity->session->totalTimerTime, 0.001);
        self::assertEqualsWithDelta(4513.0, $activity->session->totalElapsedTime, 0.001);
        self::assertEqualsWithDelta(10041.1, $activity->session->totalDistance, 0.01);
        self::assertSame(148, $activity->session->avgHeartRate);
        self::assertSame(186, $activity->session->maxHeartRate);
        self::assertNull($activity->session->avgCadence);
        self::assertNull($activity->session->avgPower);

        self::assertCount(1, $activity->laps);
        self::assertCount(4453, $activity->laps[0]->records);
        self::assertSame(TrainingPhaseIntensity::Active, $activity->laps[0]->intensity);
        self::assertNull($activity->laps[0]->avgHeartRate);
        self::assertSame('11:22:10', $activity->laps[0]->endedAt->setTimezone(new \DateTimeZone('Europe/Paris'))->format('H:i:s'));

        self::assertCount(4453, $activity->session->records);
        $first = $activity->session->records[0];
        self::assertSame(565180035, $first->latitude);
        self::assertSame(8679850, $first->longitude);
        self::assertNull($first->altitude);
        self::assertEqualsWithDelta(0.483, $first->speed, 0.001);
        self::assertSame(111, $first->heartRate);
        self::assertNull($first->cadence);
        self::assertNull($first->power);

        // The timer ran from the start to the end: a sample's running time is its wall clock.
        self::assertSame(1, $first->timerAt);
    }

    public function testReadsAnNkSpeedCoachSessionRecordedPerStroke(): void
    {
        $activity = (new FitActivityReader())->read(new File(self::FIXTURES.'/nk-speedcoach-rowing.fit'));

        self::assertSame('NK', $activity->device);
        self::assertSame(Sport::ROWING, $activity->sport);
        self::assertNull($activity->subSport);
        self::assertEqualsWithDelta(4156.406, $activity->session->totalTimerTime, 0.001);
        self::assertEqualsWithDelta(11676.92, $activity->session->totalDistance, 0.01);
        self::assertNull($activity->session->avgHeartRate);
        self::assertNull($activity->session->avgCadence);
        self::assertSame(0, $activity->session->avgPower);

        self::assertCount(1281, $activity->session->records);
        self::assertEqualsWithDelta(15.0, $activity->session->records[0]->cadence, 0.001);
        self::assertSame(111, $activity->session->records[0]->heartRate);
        self::assertEqualsWithDelta(5.24, $activity->session->records[0]->distance, 0.001);
        // One record per stroke: the first two come 3 s then 7 s after the start, not every second.
        self::assertSame(3, $activity->session->records[0]->timerAt);
        self::assertSame(7, $activity->session->records[1]->timerAt);
    }

    public function testReadsAConcept2ContinuousPieceAsASinglePhase(): void
    {
        $activity = (new FitActivityReader())->read(new File(self::FIXTURES.'/concept2-splits.fit'));

        self::assertSame('Concept2', $activity->device);
        self::assertSame(Sport::FITNESS_EQUIPMENT, $activity->sport);
        self::assertSame(SubSport::INDOOR_ROWING, $activity->subSport);
        self::assertSame('2026-08-28T16:20:00+00:00', $activity->startedAt->setTimezone(new \DateTimeZone('UTC'))->format(\DATE_ATOM));
        self::assertEqualsWithDelta(1200.0, $activity->session->totalTimerTime, 0.001);
        self::assertEqualsWithDelta(4700.0, $activity->session->totalDistance, 0.01);
        self::assertSame(168, $activity->session->avgPower);
        self::assertSame(21, $activity->session->avgCadence);

        // Concept2 writes the five splits of the piece as laps, all flagged rest: splits are not phases.
        self::assertCount(1, $activity->laps);
        self::assertSame($activity->session, $activity->laps[0]);
        self::assertSame(TrainingPhaseIntensity::Active, $activity->laps[0]->intensity);

        self::assertCount(423, $activity->session->records);
        self::assertSame(219, $activity->session->records[2]->power);
    }

    public function testReadsAConcept2IntervalWorkoutWithItsRestLap(): void
    {
        $activity = (new FitActivityReader())->read(new File(self::FIXTURES.'/concept2-intervals.fit'));

        self::assertEqualsWithDelta(2578.0, $activity->session->totalTimerTime, 0.001);
        self::assertCount(3, $activity->laps);
        self::assertSame(
            [TrainingPhaseIntensity::Active, TrainingPhaseIntensity::Rest, TrainingPhaseIntensity::Active],
            array_map(static fn ($lap) => $lap->intensity, $activity->laps),
        );
        self::assertEqualsWithDelta(169.0, $activity->laps[1]->totalTimerTime, 0.001);
        self::assertEqualsWithDelta(17.0, $activity->laps[1]->totalDistance, 0.01);
        self::assertSame(0, $activity->laps[1]->avgPower);
        self::assertCount(767, $activity->session->records);
    }

    public function testReadsAConcept2LogbookExportAsASinglePhase(): void
    {
        $activity = (new FitActivityReader())->read(new File(self::FIXTURES.'/concept2-logbook-splits.fit'));

        self::assertSame('Concept2', $activity->device);
        self::assertSame('2026-09-02T16:04:00+00:00', $activity->startedAt->setTimezone(new \DateTimeZone('UTC'))->format(\DATE_ATOM));
        self::assertEqualsWithDelta(2700.0, $activity->session->totalTimerTime, 0.001);
        self::assertEqualsWithDelta(8958.99, $activity->session->totalDistance, 0.01);
        self::assertSame(136, $activity->session->avgHeartRate);
        self::assertSame(102, $activity->session->avgPower);

        // A 45 min piece cut into five 9 min splits: one phase, with every stroke.
        self::assertCount(885, $activity->session->records);
        self::assertCount(1, $activity->laps);
        self::assertSame($activity->session, $activity->laps[0]);
    }

    public function testReadsAHeartRateOnlyIndoorSession(): void
    {
        $activity = (new FitActivityReader())->read(new File(self::FIXTURES.'/polar-indoor-cycling.fit'));

        self::assertSame(Sport::CYCLING, $activity->sport);
        self::assertSame(SubSport::INDOOR_CYCLING, $activity->subSport);
        self::assertNull($activity->session->totalDistance);
        self::assertSame(122, $activity->session->avgHeartRate);
        self::assertCount(2400, $activity->session->records);
        self::assertNull($activity->session->records[0]->speed);
        self::assertNull($activity->session->records[0]->latitude);
        self::assertSame(87, $activity->session->records[0]->heartRate);
    }

    public function testRejectsAFileThatIsNotFit(): void
    {
        $this->expectException(InvalidFitFileException::class);

        (new FitActivityReader())->read(new File(self::FIXTURES.'/not-a-fit.fit'));
    }

    public function testRejectsAnEmptyFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fit');
        file_put_contents($path, '');

        try {
            $this->expectException(InvalidFitFileException::class);
            (new FitActivityReader())->read(new File($path));
        } finally {
            unlink($path);
        }
    }

    public function testRejectsATruncatedFile(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'fit');
        file_put_contents($path, mb_substr(file_get_contents(self::FIXTURES.'/polar-rowing.fit'), 0, 4000, '8bit'));

        try {
            $this->expectException(InvalidFitFileException::class);
            (new FitActivityReader())->read(new File($path));
        } finally {
            unlink($path);
        }
    }

    public function testRejectsAFileThatDisappeared(): void
    {
        $this->expectException(InvalidFitFileException::class);

        // checkPath: false, the File is built before the path is gone — a race the reader must survive.
        (new FitActivityReader())->read(new File(self::FIXTURES.'/does-not-exist.fit', false));
    }
}
