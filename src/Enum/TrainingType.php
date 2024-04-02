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

enum TrainingType: string
{
    case B1 = 'b1';
    case B2 = 'b2';
    case B3 = 'b3';
    case B4 = 'b4';
    case B5 = 'b5';
    case B6 = 'b6';
    case B7 = 'b7';
    case B8 = 'b8';
    case C1 = 'c1';
    case C2 = 'c2';
    case Rest = 'rest';
    case GeneralizedEndurance = 'generalized_endurance';
    case SplitShort = 'split_short';
    case SplitLong = 'split_long';

    public function label(): string
    {
        return match ($this) {
            self::B1 => 'B1',
            self::B2 => 'B2',
            self::B3 => 'B3',
            self::B4 => 'B4',
            self::B5 => 'B5',
            self::B6 => 'B6',
            self::B7 => 'B7',
            self::B8 => 'B8',
            self::C1 => 'C1',
            self::C2 => 'C2',
            self::Rest => 'Récupération',
            self::GeneralizedEndurance => 'Endurance généralisée',
            self::SplitShort => 'Fractionné long',
            self::SplitLong => 'Fractionné court',
        };
    }
}
