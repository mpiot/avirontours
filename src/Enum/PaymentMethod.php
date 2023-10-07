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

enum PaymentMethod: string
{
    case Check = 'check';
    case VacationCheck = 'vacation_check';
    case Online = 'online';
    case Cash = 'cash';
    case PassSport = 'pass-sport';
    case Yelp = 'yelp';

    public function label(): string
    {
        return match ($this) {
            self::Check => 'Chèque',
            self::VacationCheck => 'Chèques vacance',
            self::Online => 'En ligne (HelloAsso)',
            self::Cash => 'Liquide',
            self::PassSport => 'Pass\'Sport',
            self::Yelp => 'Yelp',
        };
    }

    public function hasCheckNumber(): bool
    {
        return \in_array($this, [self::Check, self::VacationCheck], true);
    }

    public function hasCheckDate(): bool
    {
        return self::Check === $this;
    }
}
