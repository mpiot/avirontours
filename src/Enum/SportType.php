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

namespace App\Enum;

use App\Chart\ChartPalette;
use App\Util\DurationManipulator;

enum SportType: string
{
    // Declaration order is what the sport picker of templates/form/_training.html.twig renders.
    case Rowing = 'rowing';
    case Ergometer = 'ergometer';
    case Cycling = 'cycling';
    case WeightTraining = 'weight_training';
    case Strengthening = 'strengthening';
    case GeneralPhysicalPreparation = 'general_physical_preparation';
    case Running = 'running';
    case Swimming = 'swimming';
    case Yoga = 'yoga';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Rowing => 'Aviron',
            self::Running => 'Course à pied',
            self::Ergometer => 'Ergomètre',
            self::Strengthening => 'Gainage / Renfo.',
            self::WeightTraining => 'Musculation',
            self::Swimming => 'Natation',
            self::GeneralPhysicalPreparation => 'PPG',
            self::Cycling => 'Vélo',
            self::Yoga => 'Yoga',
            self::Other => 'Autre',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Rowing => ChartPalette::INDIGO,
            self::Running => ChartPalette::ORANGE,
            self::Ergometer => ChartPalette::TEAL,
            self::Strengthening => ChartPalette::AMBER,
            self::WeightTraining => ChartPalette::ROSE,
            self::Swimming => ChartPalette::SKY,
            self::GeneralPhysicalPreparation => ChartPalette::FUCHSIA,
            self::Cycling => ChartPalette::LIME,
            self::Yoga => ChartPalette::GREEN,
            self::Other => ChartPalette::SLATE,
        };
    }

    public function speedUnit(): ?string
    {
        return match ($this) {
            self::Rowing, self::Ergometer => '/500m',
            self::Swimming => '/100m',
            self::Running => '/km',
            self::Cycling => 'km/h',
            self::Strengthening, self::WeightTraining, self::GeneralPhysicalPreparation, self::Yoga, self::Other => null,
        };
    }

    public function formatSpeed(?int $duration, ?int $distance): ?string
    {
        if (null === $duration || null === $distance || 0 === $distance) {
            return null;
        }

        return match ($this) {
            self::Rowing, self::Ergometer => DurationManipulator::formatTenthSecondsAsHoursMinutesSecondsAndTenthSeconds((int) round(500 * $duration / $distance)),
            self::Swimming => DurationManipulator::formatSecondsAsMinutesSeconds((int) round(10 * $duration / $distance)),
            self::Running => DurationManipulator::formatSecondsAsMinutesSeconds((int) round(100 * $duration / $distance)),
            self::Cycling => number_format($distance * 36 / $duration, 1, ',', ' '),
            self::Strengthening, self::WeightTraining, self::GeneralPhysicalPreparation, self::Yoga, self::Other => null,
        };
    }

    public function tracksWatts(): bool
    {
        return self::Ergometer === $this;
    }
}
