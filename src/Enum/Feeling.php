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

enum Feeling: int
{
    case Horrible = 0;
    case Bad = 25;
    case Average = 50;
    case Good = 75;
    case VeryGood = 100;

    public function label(): string
    {
        return match ($this) {
            self::Horrible => 'Horrible',
            self::Bad => 'Mal',
            self::Average => 'Moyen',
            self::Good => 'Bien',
            self::VeryGood => 'Très bien',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Horrible => 'malade, blessé ou vidé : la séance n\'aurait peut-être pas dû avoir lieu',
            self::Bad => 'nuit courte, jambes lourdes : je me suis traîné toute la journée',
            self::Average => 'une journée ordinaire, ni élan ni frein',
            self::Good => 'en forme, le corps répond, l\'envie aussi',
            self::VeryGood => 'léger, de l\'énergie à revendre',
        };
    }
}
