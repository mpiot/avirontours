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
use App\Service\Fit\Model\FitLap;
use App\Service\Fit\Model\FitRecord;
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
use Sportlog\FIT\Profile\Types\Manufacturer;
use Sportlog\FIT\Profile\Types\MesgNum;
use Symfony\Component\HttpFoundation\File\File;

final readonly class FitActivityReader
{
    /**
     * @throws InvalidFitFileException     the bytes cannot be decoded
     * @throws UnsupportedFitFileException the file is not a single-session activity
     */
    public function read(File $file): FitActivity
    {
        $messages = $this->decodeFitFile($file);

        $fileId = $messages[MesgNum::FILE_ID][0] ?? null;
        if (!$fileId instanceof FileIdMessage || FitFileType::ACTIVITY !== $this->toInt($fileId->getType())) {
            throw new UnsupportedFitFileException('The file is not a FIT activity.');
        }

        // activity.num_sessions is unreliable (Concept2 writes 0): count the session messages.
        $sessions = $messages[MesgNum::SESSION];
        if (1 !== \count($sessions) || !$sessions[0] instanceof SessionMessage) {
            throw new UnsupportedFitFileException(\sprintf('The file holds %d sessions, one is expected.', \count($sessions)));
        }
        $session = $sessions[0];

        $startedAt = $this->toDateTime($session->getStartTime());
        if (null === $startedAt) {
            throw new UnsupportedFitFileException('The session has no start time.');
        }

        $activity = $messages[MesgNum::ACTIVITY][0] ?? null;
        $manufacturer = $this->toInt($fileId->getManufacturer());

        return new FitActivity(
            device: FitDeviceLabel::from($manufacturer, $this->toInt($fileId->getProduct()), $fileId->getProductName()),
            sport: $this->toInt($session->getSport()),
            subSport: $this->toInt($session->getSubSport()),
            startedAt: $startedAt,
            localStartedAt: $activity instanceof ActivityMessage ? $this->utcToLocalDateTime($activity->getLocalTimestamp()) : null,
            totalTimerTime: $this->toFloat($session->getTotalTimerTime()),
            totalElapsedTime: $this->toFloat($session->getTotalElapsedTime()),
            totalDistance: $this->toFloat($session->getTotalDistance()),
            avgHeartRate: $this->toInt($session->getAvgHeartRate()),
            maxHeartRate: $this->toInt($session->getMaxHeartRate()),
            avgCadence: $this->toInt($session->getAvgCadence()),
            avgPower: $this->toInt($session->getAvgPower()),
            laps: $this->getLaps($messages[MesgNum::LAP], $manufacturer),
            records: $this->getRecords($messages[MesgNum::RECORD]),
            timerEvents: $this->getTimerEvents($messages[MesgNum::EVENT]),
        );
    }

    /**
     * @return array<int, list<Message>>
     */
    private function decodeFitFile(File $file): array
    {
        // The messages we want to work with.
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
     * @param list<Message> $messages
     *
     * @return list<FitLap>
     */
    private function getLaps(array $messages, ?int $manufacturer): array
    {
        $laps = [];
        foreach ($messages as $message) {
            if (!$message instanceof LapMessage) {
                continue;
            }

            $startedAt = $this->toDateTime($message->getStartTime());
            if (null === $startedAt) {
                continue;
            }

            $elapsedTime = $this->toFloat($message->getTotalElapsedTime());
            $endedAt = $this->toDateTime($message->getTimestamp())
                ?? $startedAt->modify(\sprintf('+%d seconds', (int) round($elapsedTime ?? 0)));

            $laps[] = new FitLap(
                startedAt: $startedAt,
                endedAt: $endedAt,
                totalTimerTime: $this->toFloat($message->getTotalTimerTime()),
                totalElapsedTime: $elapsedTime,
                totalDistance: $this->toFloat($message->getTotalDistance()),
                intensity: TrainingPhaseIntensity::fromFit($this->toInt($message->getIntensity())),
                avgHeartRate: $this->toInt($message->getAvgHeartRate()),
                maxHeartRate: $this->toInt($message->getMaxHeartRate()),
                avgCadence: $this->toInt($message->getAvgCadence()),
                avgPower: $this->toInt($message->getAvgPower()),
            );
        }

        usort($laps, static fn (FitLap $a, FitLap $b): int => $a->startedAt <=> $b->startedAt);

        // Concept2 writes its interval workouts correctly (work = active, rest = rest) but flags every
        // split of a continuous piece as rest. A session cannot be rest alone, so the intensity only
        // carries information when at least one lap works — watts say nothing, a recovery may be rowed.
        $restOnly = Manufacturer::CONCEPT2 === $manufacturer
            && [] === array_filter($laps, static fn (FitLap $lap): bool => TrainingPhaseIntensity::Rest !== $lap->intensity);
        if ($restOnly) {
            $laps = array_map(static fn (FitLap $lap): FitLap => new FitLap(
                startedAt: $lap->startedAt,
                endedAt: $lap->endedAt,
                totalTimerTime: $lap->totalTimerTime,
                totalElapsedTime: $lap->totalElapsedTime,
                totalDistance: $lap->totalDistance,
                intensity: TrainingPhaseIntensity::Active,
                avgHeartRate: $lap->avgHeartRate,
                maxHeartRate: $lap->maxHeartRate,
                avgCadence: $lap->avgCadence,
                avgPower: $lap->avgPower,
            ), $laps);
        }

        return $laps;
    }

    /**
     * @param list<Message> $messages
     *
     * @return list<FitRecord>
     */
    private function getRecords(array $messages): array
    {
        $records = [];
        foreach ($messages as $message) {
            if (!$message instanceof RecordMessage) {
                continue;
            }

            $recordedAt = $this->toDateTime($message->getTimestamp());
            if (null === $recordedAt) {
                continue;
            }

            $records[] = new FitRecord(
                recordedAt: $recordedAt,
                latitude: $this->toInt($message->getPositionLat()),
                longitude: $this->toInt($message->getPositionLong()),
                altitude: $this->toFloat($message->getEnhancedAltitude()) ?? $this->toFloat($message->getAltitude()),
                distance: $this->toFloat($message->getDistance()),
                speed: $this->toFloat($message->getEnhancedSpeed()) ?? $this->toFloat($message->getSpeed()),
                heartRate: $this->toInt($message->getHeartRate()),
                cadence: $this->getCadence($message),
                power: $this->toInt($message->getPower()),
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
    private function getTimerEvents(array $messages): array
    {
        $events = [];
        foreach ($messages as $message) {
            if (!$message instanceof EventMessage || Event::TIMER !== $this->toInt($message->getEvent())) {
                continue;
            }

            $occurredAt = $this->toDateTime($message->getTimestamp());
            if (null === $occurredAt) {
                continue;
            }

            $events[] = new FitTimerEvent($occurredAt, EventType::START === $this->toInt($message->getEventType()));
        }

        usort($events, static fn (FitTimerEvent $a, FitTimerEvent $b): int => $a->occurredAt <=> $b->occurredAt);

        return $events;
    }

    private function getCadence(RecordMessage $message): ?float
    {
        $integralCadence = $this->toInt($message->getCadence());
        if (null === $integralCadence) {
            return null;
        }

        $fractionalCadence = $this->toFloat($message->getFractionalCadence()) ?? 0.0;

        return $integralCadence + $fractionalCadence;
    }

    // Multi-element fields come back as arrays: keep the first value, like the reference SDK does.
    private function toInt(mixed $value): ?int
    {
        if (\is_array($value)) {
            $value = $value[0] ?? null;
        }

        return \is_int($value) || \is_float($value) ? (int) $value : null;
    }

    private function toFloat(mixed $value): ?float
    {
        if (\is_array($value)) {
            $value = $value[0] ?? null;
        }

        return \is_int($value) || \is_float($value) ? (float) $value : null;
    }

    private function toDateTime(?\DateTime $dateTime): ?\DateTimeImmutable
    {
        return null === $dateTime ? null : \DateTimeImmutable::createFromMutable($dateTime);
    }

    private function utcToLocalDateTime(?\DateTime $localDateTime): ?\DateTimeImmutable
    {
        if (null === $localDateTime) {
            return null;
        }

        $writtenDigits = \DateTimeImmutable::createFromMutable($localDateTime)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        return new \DateTimeImmutable($writtenDigits);
    }
}
