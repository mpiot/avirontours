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

use App\Enum\TrainingPhaseIntensity;
use App\Service\Fit\Exception\InvalidFitFileException;
use App\Service\Fit\Exception\UnsupportedFitFileException;
use App\Service\Fit\Model\FitActivity;
use App\Service\Fit\Model\FitRecord;
use App\Service\Fit\Model\FitSegment;
use App\Service\Fit\Model\FitTimeline;
use App\Service\Fit\Model\FitTimerEvent;
use Sportlog\FIT\Decoder;
use Sportlog\FIT\Profile\Message;
use Sportlog\FIT\Profile\Messages\ActivityMessage;
use Sportlog\FIT\Profile\Messages\EventMessage;
use Sportlog\FIT\Profile\Messages\FileIdMessage;
use Sportlog\FIT\Profile\Messages\LapMessage;
use Sportlog\FIT\Profile\Messages\RecordMessage;
use Sportlog\FIT\Profile\Messages\SessionMessage;
use Sportlog\FIT\Profile\Types\Event;
use Sportlog\FIT\Profile\Types\EventType;
use Sportlog\FIT\Profile\Types\File as FitFileType;
use Sportlog\FIT\Profile\Types\MesgNum;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Reads a FIT activity file into a FitActivity: the session, its laps and the samples of each.
 */
final readonly class FitActivityReader
{
    /**
     * @throws InvalidFitFileException     the bytes cannot be decoded
     * @throws UnsupportedFitFileException the file is not a single-session activity
     */
    public function read(File $file): FitActivity
    {
        $messages = $this->decode($file);

        $fileId = $messages[MesgNum::FILE_ID][0] ?? null;
        if (!$fileId instanceof FileIdMessage || FitFileType::ACTIVITY !== $this->int($fileId->getType())) {
            throw new UnsupportedFitFileException('The file is not a FIT activity.');
        }

        // activity.num_sessions is unreliable (Concept2 writes 0): count the session messages.
        $sessions = $messages[MesgNum::SESSION];
        if (1 !== \count($sessions) || !$sessions[0] instanceof SessionMessage) {
            throw new UnsupportedFitFileException(\sprintf('The file holds %d sessions, one is expected.', \count($sessions)));
        }
        $session = $sessions[0];

        $startedAt = $this->dateTime($session->getStartTime());
        if (null === $startedAt) {
            throw new UnsupportedFitFileException('The session has no start time.');
        }

        $timeline = new FitTimeline($startedAt, $this->readTimerEvents($messages[MesgNum::EVENT]));
        $manufacturer = $this->int($fileId->getManufacturer());

        $records = $this->readRecords($messages[MesgNum::RECORD], $timeline);
        $laps = $this->readLaps($messages[MesgNum::LAP], $timeline);
        if (FitDeviceQuirks::lapsAreSplits($manufacturer, array_map(static fn (FitSegment $lap): TrainingPhaseIntensity => $lap->intensity, $laps))) {
            $laps = [];
        }
        $laps = $this->distribute($laps, $records);

        // The session holds what its laps hold; a file without a lap is a single phase, the session itself.
        $sessionRecords = $records;
        if ([] !== $laps) {
            $sessionRecords = array_merge(...array_map(static fn (FitSegment $lap): array => $lap->records, $laps));
        }
        $sessionSegment = $this->readSegment($session, $startedAt, $timeline, TrainingPhaseIntensity::Active, $sessionRecords);
        if ([] === $laps) {
            $laps = [$sessionSegment];
        }

        $activity = $messages[MesgNum::ACTIVITY][0] ?? null;

        return new FitActivity(
            device: FitDeviceLabel::from($manufacturer, $this->int($fileId->getProduct()), $fileId->getProductName()),
            sport: $this->int($session->getSport()),
            subSport: $this->int($session->getSubSport()),
            startedAt: $startedAt,
            localStartedAt: $activity instanceof ActivityMessage ? $this->localDateTime($activity->getLocalTimestamp()) : null,
            session: $sessionSegment,
            laps: $laps,
        );
    }

    /**
     * The messages the import reads, by message number.
     *
     * @return array<int, list<Message>>
     */
    private function decode(File $file): array
    {
        $messages = array_fill_keys([MesgNum::FILE_ID, MesgNum::ACTIVITY, MesgNum::SESSION, MesgNum::LAP, MesgNum::RECORD, MesgNum::EVENT], []);

        try {
            new Decoder()->stream($file->getPathname(), static function (Message $message) use (&$messages): void {
                if (\array_key_exists($message->getGlobalMessageNumber(), $messages)) {
                    $messages[$message->getGlobalMessageNumber()][] = $message;
                }
            });
        } catch (\Throwable $e) {
            throw new InvalidFitFileException('The file is not a decodable FIT file.', previous: $e);
        }

        return $messages;
    }

    /**
     * A session and a lap summarise the same figures over their own stretch of time.
     *
     * @param list<FitRecord> $records
     */
    private function readSegment(SessionMessage|LapMessage $message, \DateTimeImmutable $startedAt, FitTimeline $timeline, TrainingPhaseIntensity $intensity, array $records): FitSegment
    {
        $elapsedTime = $this->float($message->getTotalElapsedTime());
        $endedAt = $this->dateTime($message->getTimestamp());
        if (null === $endedAt) {
            $endedAt = $startedAt->modify(\sprintf('+%d seconds', (int) round($elapsedTime ?? 0)));
        }

        return new FitSegment(
            startedAt: $startedAt,
            endedAt: $endedAt,
            timerAt: $timeline->timerAt($startedAt),
            totalTimerTime: $this->float($message->getTotalTimerTime()),
            totalElapsedTime: $elapsedTime,
            totalDistance: $this->float($message->getTotalDistance()),
            intensity: $intensity,
            avgHeartRate: $this->int($message->getAvgHeartRate()),
            maxHeartRate: $this->int($message->getMaxHeartRate()),
            avgCadence: $this->int($message->getAvgCadence()),
            avgPower: $this->int($message->getAvgPower()),
            records: $records,
        );
    }

    /**
     * The laps in start order, with the intensity the file wrote and no samples yet.
     *
     * @param list<Message> $messages
     *
     * @return list<FitSegment>
     */
    private function readLaps(array $messages, FitTimeline $timeline): array
    {
        $laps = [];
        foreach ($messages as $message) {
            if (!$message instanceof LapMessage) {
                continue;
            }

            $startedAt = $this->dateTime($message->getStartTime());
            if (null === $startedAt) {
                continue;
            }

            $laps[] = $this->readSegment($message, $startedAt, $timeline, TrainingPhaseIntensity::fromFit($this->int($message->getIntensity())), []);
        }

        usort($laps, static fn (FitSegment $a, FitSegment $b): int => $a->startedAt <=> $b->startedAt);

        return $laps;
    }

    /**
     * The samples the timer was running for, in order, each stamped with its running time.
     *
     * @param list<Message> $messages
     *
     * @return list<FitRecord>
     */
    private function readRecords(array $messages, FitTimeline $timeline): array
    {
        $records = [];
        foreach ($messages as $message) {
            if (!$message instanceof RecordMessage) {
                continue;
            }

            $recordedAt = $this->dateTime($message->getTimestamp());
            if (null === $recordedAt || $timeline->isPaused($recordedAt)) {
                continue;
            }

            $records[] = new FitRecord(
                recordedAt: $recordedAt,
                timerAt: $timeline->timerAt($recordedAt),
                latitude: $this->int($message->getPositionLat()),
                longitude: $this->int($message->getPositionLong()),
                altitude: $this->float($message->getEnhancedAltitude()) ?? $this->float($message->getAltitude()),
                distance: $this->float($message->getDistance()),
                speed: $this->float($message->getEnhancedSpeed()) ?? $this->float($message->getSpeed()),
                heartRate: $this->int($message->getHeartRate()),
                cadence: $this->cadence($message),
                power: $this->int($message->getPower()),
            );
        }

        usort($records, static fn (FitRecord $a, FitRecord $b): int => $a->recordedAt <=> $b->recordedAt);

        return $records;
    }

    /**
     * @param list<Message> $messages
     *
     * @return list<FitTimerEvent>
     */
    private function readTimerEvents(array $messages): array
    {
        $events = [];
        foreach ($messages as $message) {
            if (!$message instanceof EventMessage || Event::TIMER !== $this->int($message->getEvent())) {
                continue;
            }

            $occurredAt = $this->dateTime($message->getTimestamp());
            if (null === $occurredAt) {
                continue;
            }

            $events[] = new FitTimerEvent($occurredAt, EventType::START === $this->int($message->getEventType()));
        }

        usort($events, static fn (FitTimerEvent $a, FitTimerEvent $b): int => $a->occurredAt <=> $b->occurredAt);

        return $events;
    }

    /**
     * Hands each sample to the lap whose window holds it. A sample stamped at a lap end closes that lap
     * (the last stroke of an interval, not the first sample of the rest); samples outside every lap are dropped.
     *
     * @param list<FitSegment> $laps    in start order, without their samples
     * @param list<FitRecord>  $records in order
     *
     * @return list<FitSegment>
     */
    private function distribute(array $laps, array $records): array
    {
        if ([] === $laps) {
            return [];
        }

        $recordsByLap = array_fill(0, \count($laps), []);
        $lastLap = \count($laps) - 1;
        $current = 0;
        foreach ($records as $record) {
            $timestamp = $record->recordedAt->getTimestamp();

            // Samples are chronological: move on once the current lap is over.
            while ($current < $lastLap && $timestamp > $laps[$current]->endedAt->getTimestamp()) {
                ++$current;
            }

            if ($this->holds($laps[$current], $timestamp, 0 === $current)) {
                $recordsByLap[$current][] = $record;
            }
        }

        $distributed = [];
        foreach ($laps as $index => $lap) {
            $distributed[] = $lap->withRecords($recordsByLap[$index]);
        }

        return $distributed;
    }

    private function holds(FitSegment $lap, int $timestamp, bool $isFirstLap): bool
    {
        $startedAt = $lap->startedAt->getTimestamp();
        $afterStart = $isFirstLap ? $timestamp >= $startedAt : $timestamp > $startedAt;

        return $afterStart && $timestamp <= $lap->endedAt->getTimestamp();
    }

    /**
     * FIT splits the cadence in a whole part and a fractional one.
     */
    private function cadence(RecordMessage $message): ?float
    {
        $wholeCadence = $this->int($message->getCadence());
        if (null === $wholeCadence) {
            return null;
        }

        return $wholeCadence + ($this->float($message->getFractionalCadence()) ?? 0.0);
    }

    // Multi-element fields come back as arrays: keep the first value, like the reference SDK does.
    private function int(mixed $value): ?int
    {
        if (\is_array($value)) {
            $value = $value[0] ?? null;
        }

        return \is_int($value) || \is_float($value) ? (int) $value : null;
    }

    private function float(mixed $value): ?float
    {
        if (\is_array($value)) {
            $value = $value[0] ?? null;
        }

        return \is_int($value) || \is_float($value) ? (float) $value : null;
    }

    private function dateTime(?\DateTime $dateTime): ?\DateTimeImmutable
    {
        if (null === $dateTime) {
            return null;
        }

        return \DateTimeImmutable::createFromMutable($dateTime);
    }

    /**
     * The device writes its wall clock as if it were UTC: keep the digits, drop the zone.
     */
    private function localDateTime(?\DateTime $localDateTime): ?\DateTimeImmutable
    {
        if (null === $localDateTime) {
            return null;
        }

        $writtenDigits = \DateTimeImmutable::createFromMutable($localDateTime)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        return new \DateTimeImmutable($writtenDigits);
    }
}
