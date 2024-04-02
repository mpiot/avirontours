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

enum EnergyPathwayType: string
{
    case Aerobic = 'aerobic';
    case Threshold = 'threshold';
    case LacticAnaerobic = 'lactic_aerobic';
    case AlacticAnaerobic = 'alactic_aerobic';

    public function label(): string
    {
        return match ($this) {
            self::Aerobic => 'Aérobie',
            self::Threshold => 'Transition aérobie/anaérobie',
            self::LacticAnaerobic => 'Anaérobie lactique',
            self::AlacticAnaerobic => 'Anaérobie alactique',
        };
    }

    public static function fromTrainingType(TrainingType $trainingType): self
    {
        return match ($trainingType) {
            TrainingType::B1, TrainingType::B2, TrainingType::Rest, TrainingType::GeneralizedEndurance => self::Aerobic,
            TrainingType::B3, TrainingType::B4, TrainingType::B7, TrainingType::C2, TrainingType::SplitLong => self::Threshold,
            TrainingType::B5, TrainingType::SplitShort => self::LacticAnaerobic,
            TrainingType::B6, TrainingType::B8, TrainingType::C1 => self::AlacticAnaerobic,
        };
    }
}
