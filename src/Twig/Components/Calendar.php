<?php

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

namespace App\Twig\Components;

use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
final class Calendar
{
    private int $year;

    private int $month;

    private array $weeks = [];

    public \DateTimeImmutable $currentMonth;

    public ?\DateTimeImmutable $minDate;

    public ?\DateTimeImmutable $maxDate;

    public string $navigationRouteName;

    public array $navigationRouteParams = [];

    public string $newEventRouteName;

    public array $newEventRouteParams = [];

    public string $newEventButtonText = 'Nouvel évènement';

    public function __construct(private readonly RouterInterface $router)
    {
    }

    public function mount(int $year, int $month, array $events): void
    {
        $this->year = $year;
        $this->month = $month;
        $this->currentMonth = new \DateTimeImmutable("{$this->year}-{$this->month}-01");
        $this->generateWeeks($events);
    }

    public function getWeeks(): array
    {
        return $this->weeks;
    }

    public function getPreviousMonthPath(): ?string
    {
        $previousMonth = $this->currentMonth->modify('- 1 month');
        $year = $previousMonth->format('Y');
        $month = $previousMonth->format('n');

        if (
            null !== $this->minDate
            && (
                $year < $this->minDate->format('Y')
                || ($year === $this->minDate->format('Y') && $month < $this->minDate->format('n'))
            )
        ) {
            return null;
        }

        return $this->generatePath(
            $this->navigationRouteName,
            $this->navigationRouteParams,
            ['year' => $year, 'month' => $month]
        );
    }

    public function getNextMonthPath(): ?string
    {
        $nextMonth = $this->currentMonth->modify('+ 1 month');
        $year = $nextMonth->format('Y');
        $month = $nextMonth->format('n');

        if (
            null !== $this->maxDate
            && (
                $year > $this->maxDate->format('Y')
                || ($year === $this->maxDate->format('Y') && $month > $this->maxDate->format('n'))
            )
        ) {
            return null;
        }

        return $this->generatePath(
            $this->navigationRouteName,
            $this->navigationRouteParams,
            ['year' => $year, 'month' => $month]
        );
    }

    public function getNewEventPath(?\DateTimeImmutable $date = null): string
    {
        $extraParams = [];
        if (null !== $date) {
            $extraParams = ['date' => $date->format('Y-m-d')];
        }

        return $this->generatePath(
            $this->newEventRouteName,
            $this->newEventRouteParams,
            $extraParams
        );
    }

    public function isDisabled(\DateTimeImmutable $date): bool
    {
        return $date < $this->minDate || $date > $this->maxDate;
    }

    public function isPartOfMonth(\DateTimeImmutable $date): bool
    {
        return $this->month === (int) $date->format('n');
    }

    private function generateWeeks(array $events): void
    {
        $firstDayOfTheMonth = $this->currentMonth->modify('first day of this month midnight');
        $lastDayOfTheMonth = $this->currentMonth->modify('last day of this month midnight');
        $firstDayOfTheFirstWeekOfTheMonth = '1' === $firstDayOfTheMonth->format('N') ? $firstDayOfTheMonth : $firstDayOfTheMonth->modify('last monday');
        $lastDayOfTheLastWeekOfTheMonth = '7' === $lastDayOfTheMonth->format('N') ? $lastDayOfTheMonth : $lastDayOfTheMonth->modify('next sunday');
        $days = new \DatePeriod(
            $firstDayOfTheFirstWeekOfTheMonth,
            new \DateInterval('P1D'),
            $lastDayOfTheLastWeekOfTheMonth->modify('+1 day')
        );

        $weeks = [];
        foreach ($days as $day) {
            $dayEvents = array_filter($events, fn (array $event) => $day->format('Y-m-d') === $event['date']);

            $weeks[$day->format('W')][$day->format('Y-m-d')] = [
                'dateTime' => $day,
                'events' => $dayEvents,
            ];
        }

        $this->weeks = $weeks;
    }

    private function generatePath(string $routeName, array $routeParams, array $calendarParams): string
    {
        return $this->router->generate($routeName, array_merge($routeParams, $calendarParams));
    }
}
