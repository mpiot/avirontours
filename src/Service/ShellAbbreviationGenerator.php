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

namespace App\Service;

use App\Entity\Shell;

class ShellAbbreviationGenerator
{
    public function generateAbbreviation(Shell $shell): string
    {
        $numberRowers = $shell->getNumberRowers();
        $coxed = $shell->getCoxed();
        $yolette = $shell->getYolette();
        $type = $shell->getRowingType();

        if (Shell::ROWING_TYPE_SCULL === $type) {
            return $this->getScullAbbreviation($numberRowers, $coxed, $yolette);
        }

        if (Shell::ROWING_TYPE_SWEEP === $type) {
            return $this->getSweepAbbreviation($numberRowers, $coxed, $yolette);
        }

        return $this->getScullAbbreviation($numberRowers, $coxed, $yolette).'/'.$this->getSweepAbbreviation($numberRowers, $coxed, $yolette);
    }

    private function getScullAbbreviation(int $numberRower, bool $coxed, bool $yolette): string
    {
        $abbreviation = $numberRower;
        if (true === $yolette) {
            $abbreviation = $numberRower.'Y';
        }

        $abbreviation .= 'x';

        if (true === $coxed) {
            $abbreviation .= '+';
        }

        return $abbreviation;
    }

    private function getSweepAbbreviation(int $numberRower, bool $coxed, bool $yolette): string
    {
        $abbreviation = $numberRower;
        if (true === $yolette) {
            $abbreviation = $numberRower.'Y';
        }

        if (true === $coxed) {
            $abbreviation .= '+';
        } else {
            $abbreviation .= '-';
        }

        return $abbreviation;
    }
}
