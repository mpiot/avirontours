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

namespace App\Service;

use Symfony\Component\String\Inflector\EnglishInflector;
use function Symfony\Component\String\u;

class ArrayNormalizer
{
    public function normalize(array $arrayToNormalize): array
    {
        // Identify keys
        $keys = $this->getKeys($arrayToNormalize[0]);

        // Generate the normalized array
        $normalized = [];
        foreach ($arrayToNormalize as $values) {
            foreach ($keys as $key) {
                $normalized[$key['plural']][] = $values[$key['singular']];
            }
        }

        return $normalized;
    }

    public function group(array $arrayToGroup, string $keyName, string $valueName, array $defaultValue, string $groupBy = 'year'): array
    {
        // Generate the normalized array
        $array = [];
        foreach ($arrayToGroup as $values) {
            if (!\array_key_exists($values[$groupBy], $array)) {
                $array[$values[$groupBy]] = array_merge([
                    $groupBy => $values[$groupBy],
                ], $defaultValue);
            }

            $array[$values[$groupBy]][$values[$keyName]] = $values[$valueName];
        }

        return array_values($array);
    }

    public function fillMissingMonths(array $data, \DateTime $start, \DateTime $end, array $defaultValue, string $fieldName = 'month'): array
    {
        $start->modify('first day of this month');
        $end->modify('first day of next month');

        $interval = \DateInterval::createFromDateString('1 month');
        $period = new \DatePeriod($start, $interval, $end);

        $array = [];
        foreach ($period as $date) {
            $monthNumber = (int) $date->format('n');

            $subset = $defaultValue;
            foreach ($data as $value) {
                if ($monthNumber === (int) $value[$fieldName]) {
                    $subset = $value;
                }
            }

            $subset[$fieldName] = $monthNumber;
            $array[] = $subset;
        }

        return $array;
    }

    public function formatMonthNames(array $data, string $field = 'months'): array
    {
        foreach ($data[$field] as &$month) {
            $formatter = \IntlDateFormatter::create('fr', \IntlDateFormatter::NONE, \IntlDateFormatter::NONE);
            $formatter->setPattern('MMMM');
            $month = $formatter->format((new \DateTime())->setDate(0, $month, 0));
            $month = u($month)->title();
        }

        return $data;
    }

    private function getKeys(array $array): array
    {
        $singularKeys = array_keys($array);

        $keys = [];
        $inflector = new EnglishInflector();
        foreach ($singularKeys as $singularKey) {
            $keys[] = [
                'singular' => $singularKey,
                'plural' => $inflector->pluralize($singularKey)[0],
            ];
        }

        return $keys;
    }
}
