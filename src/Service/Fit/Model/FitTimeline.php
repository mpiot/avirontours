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

namespace App\Service\Fit\Model;

/**
 * The session timer: the stop/start pairs it was put on hold by, and the running time they take out.
 */
final readonly class FitTimeline
{
    /**
     * @var list<array{int, int}>
     */
    private array $pauses;

    /**
     * @param list<FitTimerEvent> $events sorted by instant
     */
    public function __construct(private \DateTimeImmutable $startedAt, array $events)
    {
        $pauses = [];
        $stoppedAt = null;
        foreach ($events as $event) {
            if (false === $event->started) {
                $stoppedAt ??= $event->occurredAt->getTimestamp();
                continue;
            }

            if (null !== $stoppedAt) {
                $pauses[] = [$stoppedAt, $event->occurredAt->getTimestamp()];
                $stoppedAt = null;
            }
        }

        // A trailing stop without a restart is the end of the session, not a pause: it is dropped here.
        $this->pauses = $pauses;
    }

    /**
     * Seconds of running timer between the session start and $at.
     */
    public function timerAt(\DateTimeImmutable $at): int
    {
        $timestamp = $at->getTimestamp();

        $paused = 0;
        foreach ($this->pauses as [$stoppedAt, $restartedAt]) {
            // A pause still running at $at only counts for what it has already taken; one starting
            // after $at would count negatively, hence the guard.
            if ($stoppedAt < $timestamp) {
                $paused += min($timestamp, $restartedAt) - $stoppedAt;
            }
        }

        return $timestamp - $this->startedAt->getTimestamp() - $paused;
    }

    public function isPaused(\DateTimeImmutable $at): bool
    {
        $timestamp = $at->getTimestamp();
        foreach ($this->pauses as [$stoppedAt, $restartedAt]) {
            if ($stoppedAt <= $timestamp && $timestamp < $restartedAt) {
                return true;
            }
        }

        return false;
    }
}
