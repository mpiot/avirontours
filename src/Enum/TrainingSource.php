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

enum TrainingSource: string
{
    case Manual = 'manual';
    case Logbook = 'logbook';
    case Concept2 = 'concept2';
    case Fit = 'fit';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Saisie manuelle',
            self::Logbook => 'Cahier de sorties',
            self::Concept2 => 'Logbook Concept2',
            self::Fit => 'Fichier FIT',
        };
    }

    /**
     * An imported session keeps what the device measured: sport, date, duration and distance are not editable.
     */
    public function locksMeasures(): bool
    {
        return match ($this) {
            self::Concept2, self::Fit => true,
            self::Manual, self::Logbook => false,
        };
    }
}
