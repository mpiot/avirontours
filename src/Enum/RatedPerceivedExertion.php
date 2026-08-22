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

enum RatedPerceivedExertion: int
{
    case VeryVeryEasy = 1;
    case Easy = 2;
    case Moderate = 3;
    case SomewhatHard = 4;
    case Hard = 5;
    case ReallyHard = 6;
    case VeryHard = 7;
    case ExtremelyHard = 8;
    case AlmostMaximal = 9;
    case Maximal = 10;

    public function label(): string
    {
        return match ($this) {
            self::VeryVeryEasy => 'Très très facile',
            self::Easy => 'Facile',
            self::Moderate => 'Modéré',
            self::SomewhatHard => 'Assez dur',
            self::Hard => 'Dur',
            self::ReallyHard => 'Vraiment dur',
            self::VeryHard => 'Très dur',
            self::ExtremelyHard => 'Extrêmement dur',
            self::AlmostMaximal => 'Presque maximal',
            self::Maximal => 'Maximal',
        };
    }

    /**
     * @return array{breath: string, muscular: string}
     */
    public function descriptions(): array
    {
        return [
            'breath' => $this->breathDescription(),
            'muscular' => $this->muscularDescription(),
        ];
    }

    private function breathDescription(): string
    {
        return match ($this) {
            self::VeryVeryEasy => 'conversation sans effort, comme au repos',
            self::Easy => 'échauffement : la conversation reste normale',
            self::Moderate => 'je parle par phrases entières, des heures à cette allure',
            self::SomewhatHard => 'phrases entières, mais je place ma respiration entre',
            self::Hard => 'phrases courtes, je tiendrais environ une heure',
            self::ReallyHard => 'quelques mots à la fois, 40 à 50 minutes',
            self::VeryHard => 'oui ou non, et je compte les minutes : 20 à 30 minutes',
            self::ExtremelyHard => 'je ne parle plus, je tiens : 10 à 15 minutes',
            self::AlmostMaximal => 'quelques minutes de plus, pas davantage',
            self::Maximal => 'je n\'avais plus rien à donner',
        };
    }

    private function muscularDescription(): string
    {
        return match ($this) {
            self::VeryVeryEasy => 'barre à vide, mobilité : aucune fatigue',
            self::Easy => 'charges très légères, je ressors comme je suis entré',
            self::Moderate => '5 reps en réserve partout, aucune série difficile',
            self::SomewhatHard => '4 reps en réserve, la dernière série se sent un peu',
            self::Hard => '3 reps en réserve, technique nette du début à la fin',
            self::ReallyHard => '2 à 3 reps en réserve, les dernières demandent de l\'attention',
            self::VeryHard => '2 reps en réserve, j\'ai pris toute ma récupération',
            self::ExtremelyHard => '1 rep en réserve, la technique se dégrade en fin de série',
            self::AlmostMaximal => 'une ou deux séries à l\'échec, pas une de plus dans le réservoir',
            self::Maximal => 'test de force, ou l\'échec sur presque toutes les séries',
        };
    }
}
