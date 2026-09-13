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

namespace App\Service\Fit;

use App\Entity\Training;
use App\Entity\TrainingPhase;
use App\Entity\User;
use App\Enum\SportType;
use App\Enum\TrainingSource;
use App\Service\Fit\Exception\UnsupportedFitFileException;
use App\Service\Fit\Model\FitActivity;
use App\Service\Fit\Model\FitRecord;
use App\Service\Fit\Model\FitSegment;

/**
 * Turns a FitActivity into a Training and its phases, in the application units: tenths of a second,
 * metres, tenths of a second per 500 m, whole bpm / spm / watts, semicircles.
 */
final readonly class FitActivityMapper
{
    // A stopped record: 4:00/500 m keeps the Concept2 chart scale for rowing, 1 h/500 m marks "stopped" elsewhere.
    private const int STOPPED_PACE_ROWING = 2400;
    private const int STOPPED_PACE = 36000;

    // [min, max] validity windows: outside them a sample is a sensor glitch and counts as missing.
    private const array HEART_RATE_WINDOW = [25, 250];
    private const array CADENCE_WINDOW = [5, 255];
    private const array ROWING_CADENCE_WINDOW = [5, 70];
    private const array POSITIVE = [1, \PHP_INT_MAX];
    private const array NON_NEGATIVE = [0, \PHP_INT_MAX];
    private const array ANY = [\PHP_INT_MIN, \PHP_INT_MAX];

    public function createTraining(User $user, FitActivity $activity): Training
    {
        $sport = SportType::fromFit($activity->sport, $activity->subSport);
        $session = $activity->session;
        $trainedAt = $activity->localStartedAt ?? $activity->startedAt->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        $training = new Training($user);
        $training
            ->setSource(TrainingSource::Fit)
            ->setSport($sport)
            ->setTrainedAt(\DateTime::createFromImmutable($trainedAt))
            ->setDuration((int) round($this->duration($session) * 10))
            ->setDistance($this->metres($session->totalDistance))
            ->setDevice($activity->device)
        ;
        $this->setAggregates($training, $session, $sport);

        foreach ($activity->laps as $lap) {
            $training->addTrainingPhase($this->createTrainingPhase($lap, $sport));
        }

        return $training;
    }

    private function createTrainingPhase(FitSegment $lap, SportType $sport): TrainingPhase
    {
        $records = $lap->records;
        $heartRates = $this->heartRates($records);

        $phase = new TrainingPhase();
        $phase
            ->setDuration((int) round(($lap->totalTimerTime ?? $lap->totalElapsedTime ?? 0.0) * 10))
            ->setDistance($this->metres($lap->totalDistance) ?? 0)
            ->setIntensity($lap->intensity)
            ->setEndingHeartRate(array_last($this->filterValid($heartRates, self::HEART_RATE_WINDOW)))
        ;
        $this->setAggregates($phase, $lap, $sport);

        if ([] === $records) {
            return $phase;
        }

        $times = $this->times($lap);
        $phase
            ->setTimes($times)
            ->setDistances($this->buildSeries($this->distances($records), self::NON_NEGATIVE))
            ->setPaces($this->buildSeries($this->paces($records, $sport), self::POSITIVE))
            ->setStrokeRates($this->buildSeries($this->cadences($records, $sport), $this->cadenceWindow($sport)))
            ->setHeartRates($this->buildSeries($heartRates, self::HEART_RATE_WINDOW))
            ->setPowers($this->buildSeries($this->powers($records), self::NON_NEGATIVE))
        ;
        $this->addGpsSeries($phase, $records, $times);

        return $phase;
    }

    /**
     * The averages and maxima of a stretch: the FIT figures when the file carries them, else what its
     * samples say, else nothing.
     */
    private function setAggregates(Training|TrainingPhase $target, FitSegment $segment, SportType $sport): void
    {
        $heartRates = $this->filterValid($this->heartRates($segment->records), self::HEART_RATE_WINDOW);
        $cadences = $this->filterValid($this->cadences($segment->records, $sport), $this->cadenceWindow($sport));
        $powers = $this->filterValid($this->powers($segment->records), self::POSITIVE);

        $target
            ->setAverageHeartRate($this->average($segment->avgHeartRate, $heartRates))
            ->setMaxHeartRate($this->max($segment->maxHeartRate, $heartRates))
            ->setStrokeRate($this->average($this->cadence($sport, $segment->avgCadence), $cadences))
            ->setAveragePower($this->average($segment->avgPower, $powers))
        ;
    }

    /**
     * GPS: only the records with a fix, so a signal loss is a gap in positionTimes.
     *
     * @param list<FitRecord> $records
     * @param list<int>       $times
     */
    private function addGpsSeries(TrainingPhase $phase, array $records, array $times): void
    {
        $positionTimes = [];
        $latitudes = [];
        $longitudes = [];
        $altitudes = [];
        foreach ($records as $index => $record) {
            if (null === $record->latitude || null === $record->longitude) {
                continue;
            }

            $positionTimes[] = $times[$index];
            $latitudes[] = $record->latitude;
            $longitudes[] = $record->longitude;
            $altitudes[] = null === $record->altitude ? null : (int) round($record->altitude);
        }

        if ([] === $positionTimes) {
            return;
        }

        $phase
            ->setPositionTimes($positionTimes)
            ->setLatitudes($latitudes)
            ->setLongitudes($longitudes)
            ->setAltitudes($this->buildSeries($altitudes, self::ANY))
        ;
    }

    /**
     * Seconds of running timer, from the FIT summary or from the last sample when the summary is missing.
     */
    private function duration(FitSegment $session): float
    {
        $duration = $session->totalTimerTime ?? $session->totalElapsedTime ?? 0.0;
        if ($duration <= 0) {
            $lastRecord = array_last($session->records);
            $duration = null === $lastRecord ? 0.0 : (float) $lastRecord->timerAt;
        }

        if ($duration <= 0) {
            throw new UnsupportedFitFileException('The session has no duration.');
        }

        return $duration;
    }

    /**
     * Tenths of a second of running timer since the lap start, one per sample.
     *
     * @return list<int>
     */
    private function times(FitSegment $lap): array
    {
        return array_map(static fn (FitRecord $record): int => ($record->timerAt - $lap->timerAt) * 10, $lap->records);
    }

    /**
     * Metres since the first sample that carries a distance, one per sample.
     *
     * @param list<FitRecord> $records
     *
     * @return list<int|null>
     */
    private function distances(array $records): array
    {
        $firstDistance = null;
        foreach ($records as $record) {
            if (null !== $record->distance) {
                $firstDistance = $record->distance;
                break;
            }
        }

        return array_map(static fn (FitRecord $record): ?int => null === $record->distance ? null : (int) round($record->distance - $firstDistance), $records);
    }

    /**
     * Tenths of a second per 500 m, one per sample, capped at the sport's stopped pace.
     *
     * @param list<FitRecord> $records
     *
     * @return list<int|null>
     */
    private function paces(array $records, SportType $sport): array
    {
        $stoppedPace = $this->isRowing($sport) ? self::STOPPED_PACE_ROWING : self::STOPPED_PACE;

        return array_map(static function (FitRecord $record) use ($stoppedPace): ?int {
            if (null === $record->speed) {
                return null;
            }

            return $record->speed <= 0 ? $stoppedPace : min($stoppedPace, (int) round(5000 / $record->speed));
        }, $records);
    }

    /**
     * @param list<FitRecord> $records
     *
     * @return list<int|null>
     */
    private function heartRates(array $records): array
    {
        return array_map(static fn (FitRecord $record): ?int => $record->heartRate, $records);
    }

    /**
     * @param list<FitRecord> $records
     *
     * @return list<int|null>
     */
    private function cadences(array $records, SportType $sport): array
    {
        return array_map(fn (FitRecord $record): ?int => $this->cadence($sport, $record->cadence), $records);
    }

    /**
     * @param list<FitRecord> $records
     *
     * @return list<int|null>
     */
    private function powers(array $records): array
    {
        return array_map(static fn (FitRecord $record): ?int => $record->power, $records);
    }

    /**
     * FIT cadence is per minute of what the sport counts: strokes for rowing, revolutions for cycling,
     * strides of one leg for running — which runners read doubled, as steps.
     */
    private function cadence(SportType $sport, ?float $rpm): ?int
    {
        if (null === $rpm) {
            return null;
        }

        return (int) round(SportType::Running === $sport ? 2 * $rpm : $rpm);
    }

    /**
     * @return array{int, int}
     */
    private function cadenceWindow(SportType $sport): array
    {
        return $this->isRowing($sport) ? self::ROWING_CADENCE_WINDOW : self::CADENCE_WINDOW;
    }

    private function isRowing(SportType $sport): bool
    {
        return '/500m' === $sport->speedUnit();
    }

    /**
     * A distance of zero is a device that measures none.
     */
    private function metres(?float $distance): ?int
    {
        return null !== $distance && $distance > 0 ? (int) round($distance) : null;
    }

    /**
     * @param list<int> $values
     */
    private function average(?int $fitValue, array $values): ?int
    {
        if (null !== $fitValue && $fitValue > 0) {
            return $fitValue;
        }

        return [] === $values ? null : (int) round(array_sum($values) / \count($values));
    }

    /**
     * @param list<int> $values
     */
    private function max(?int $fitValue, array $values): ?int
    {
        if (null !== $fitValue && $fitValue > 0) {
            return $fitValue;
        }

        return [] === $values ? null : max($values);
    }

    /**
     * @param list<int|null>  $values
     * @param array{int, int} $window
     *
     * @return list<int>
     */
    private function filterValid(array $values, array $window): array
    {
        [$min, $max] = $window;

        return array_values(array_filter($values, static fn (?int $value): bool => null !== $value && $value >= $min && $value <= $max));
    }

    /**
     * A series aligned with the samples: an out-of-window or missing one repeats the previous value
     * (the first valid one for a leading gap); null when no sample is valid.
     *
     * @param list<int|null>  $values
     * @param array{int, int} $window
     *
     * @return list<int>|null
     */
    private function buildSeries(array $values, array $window): ?array
    {
        $valid = $this->filterValid($values, $window);
        if ([] === $valid) {
            return null;
        }

        [$min, $max] = $window;
        $previous = $valid[0];
        $series = [];
        foreach ($values as $value) {
            if (null !== $value && $value >= $min && $value <= $max) {
                $previous = $value;
            }
            $series[] = $previous;
        }

        return $series;
    }
}
