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
    case WorkoutEndurance = 'workout_endurance';
    case WorkoutStrength = 'workout_strength';
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
            self::WorkoutEndurance => 'Musculation (Endurance)',
            self::WorkoutStrength => 'Musculation (Force)',
            self::Swimming => 'Natation',
            self::GeneralPhysicalPreparation => 'PPG',
            self::Strengthening => 'Renforcement',
            self::Cycling => 'Vélo',
            self::Yoga => 'Yoga',
        };
    }
}
