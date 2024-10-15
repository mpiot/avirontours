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

namespace App\Enum;

enum SportType: string
{
    case Other = 'other';
    case Rowing = 'rowing';
    case Running = 'running';
    case Ergometer = 'ergometer';
    case WeightTraining = 'weight_training';
    case Swimming = 'swimming';
    case GeneralPhysicalPreparation = 'general_physical_preparation';
    case Strengthening = 'strengthening';
    case Cycling = 'cycling';
    case Yoga = 'yoga';

    public function label(): string
    {
        return match ($this) {
            self::Other => 'Autre',
            self::Rowing => 'Aviron',
            self::Running => 'Course à pied',
            self::Ergometer => 'Ergomètre',
            self::WeightTraining => 'Musculation',
            self::Swimming => 'Natation',
            self::GeneralPhysicalPreparation => 'PPG',
            self::Strengthening => 'Gainage / Renforcement',
            self::Cycling => 'Vélo',
            self::Yoga => 'Yoga',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Other, self::Running, self::Swimming, self::GeneralPhysicalPreparation, self::Cycling , self::Yoga => 'rgb(194, 202, 202)',
            self::Rowing => 'rgb(70, 199, 238)',
            self::Ergometer => 'rgb(249, 191, 28)',
            self::WeightTraining => 'rgb(176, 42, 55)',
            self::Strengthening => 'rgb(210, 103, 240)',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Other, self::GeneralPhysicalPreparation => 'mdi:human-handsup',
            self::Rowing => 'mdi:rowing',
            self::Running => 'mdi:run',
            self::Ergometer => 'concept2',
            self::WeightTraining => 'mdi:weight-lifter',
            self::Swimming => 'mdi:swim',
            self::Strengthening => 'mdi:human',
            self::Cycling => 'mdi:bike',
            self::Yoga => 'mdi:yoga',
        };
    }
}
