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

enum SportSpecificity: string
{
    case Specific = 'specific';
    case SemiSpecific = 'semi_specific';
    case NonSpecific = 'non_specific';

    public function label(): string
    {
        return match ($this) {
            self::Specific => 'Spécifique',
            self::SemiSpecific => 'Semi-spécifique',
            self::NonSpecific => 'Non-spécifique',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Specific => '#2a78d6',
            self::SemiSpecific => '#4a3aa7',
            self::NonSpecific => '#eb6834',
        };
    }
}
