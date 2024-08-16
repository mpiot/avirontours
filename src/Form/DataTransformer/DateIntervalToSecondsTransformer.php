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

namespace App\Form\DataTransformer;

use App\Util\DurationManipulator;
use Symfony\Component\Form\DataTransformerInterface;

class DateIntervalToSecondsTransformer implements DataTransformerInterface
{
    /**
     * Transforms an integer (seconds) to a DateInterval.
     *
     * @param int $value
     */
    public function transform($value): ?\DateInterval
    {
        if (null === $value) {
            return null;
        }

        return DurationManipulator::tenthSecondsToDateInterval($value);
    }

    /**
     * Transforms a DateInterval to an integer (seconds).
     *
     * @param ?\DateInterval $value
     */
    public function reverseTransform($value): ?int
    {
        if (null === $value) {
            return null;
        }

        return DurationManipulator::dateIntervalToTenthSeconds($value);
    }
}
