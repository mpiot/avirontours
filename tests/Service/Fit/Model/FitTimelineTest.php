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

namespace App\Tests\Service\Fit\Model;

use App\Service\Fit\Model\FitTimeline;
use App\Service\Fit\Model\FitTimerEvent;
use PHPUnit\Framework\TestCase;

class FitTimelineTest extends TestCase
{
    private const string START = '2026-08-29 08:00:00 UTC';

    public function testWithoutAnEventTheTimerNeverStops(): void
    {
        $timeline = $this->timeline();

        self::assertSame(120, $timeline->timerAt($this->at(120)));
        self::assertFalse($timeline->isPaused($this->at(120)));
    }

    public function testAStopStartPairIsTakenOutOfTheRunningTime(): void
    {
        $timeline = $this->timeline([$this->event(0, true), $this->event(100, false), $this->event(160, true), $this->event(300, false)]);

        self::assertSame(50, $timeline->timerAt($this->at(50)));
        self::assertSame(100, $timeline->timerAt($this->at(130)));
        self::assertSame(140, $timeline->timerAt($this->at(200)));
        self::assertSame(240, $timeline->timerAt($this->at(300)));
    }

    public function testEveryPauseOfTheSessionAddsUp(): void
    {
        $timeline = $this->timeline([$this->event(0, true), $this->event(60, false), $this->event(90, true), $this->event(200, false), $this->event(230, true)]);

        self::assertSame(240, $timeline->timerAt($this->at(300)));
    }

    public function testATrailingStopIsTheEndOfTheSessionNotAPause(): void
    {
        $timeline = $this->timeline([$this->event(0, true), $this->event(100, false)]);

        self::assertSame(150, $timeline->timerAt($this->at(150)));
        self::assertFalse($timeline->isPaused($this->at(150)));
    }

    public function testTheTimerIsOnHoldFromTheStopUntilTheRestart(): void
    {
        $timeline = $this->timeline([$this->event(0, true), $this->event(100, false), $this->event(160, true)]);

        self::assertFalse($timeline->isPaused($this->at(99)));
        self::assertTrue($timeline->isPaused($this->at(100)));
        self::assertTrue($timeline->isPaused($this->at(159)));
        self::assertFalse($timeline->isPaused($this->at(160)));
    }

    /**
     * @param list<FitTimerEvent> $events
     */
    private function timeline(array $events = []): FitTimeline
    {
        return new FitTimeline(new \DateTimeImmutable(self::START), $events);
    }

    private function event(int $offset, bool $started): FitTimerEvent
    {
        return new FitTimerEvent($this->at($offset), $started);
    }

    private function at(int $offset): \DateTimeImmutable
    {
        return (new \DateTimeImmutable(self::START))->modify(\sprintf('%+d seconds', $offset));
    }
}
