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

use Sportlog\FIT\Profile\Types\Intensity;

enum TrainingPhaseIntensity: string
{
    case Active = 'active';
    case Rest = 'rest';
    case WarmUp = 'warm_up';
    case CoolDown = 'cool_down';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Travail',
            self::Rest => 'Repos',
            self::WarmUp => 'Échauffement',
            self::CoolDown => 'Retour au calme',
        };
    }

    public static function fromFit(?int $intensity): self
    {
        return match ($intensity) {
            Intensity::REST, Intensity::RECOVERY => self::Rest,
            Intensity::WARMUP => self::WarmUp,
            Intensity::COOLDOWN => self::CoolDown,
            default => self::Active,
        };
    }
}
