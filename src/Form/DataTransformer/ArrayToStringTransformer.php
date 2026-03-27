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

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\String\AbstractString;

use function Symfony\Component\String\u;

class ArrayToStringTransformer implements DataTransformerInterface
{
    public const string FLOAT = 'float';

    public const string INT = 'int';

    public const string STRING = 'string';

    public function __construct(private readonly string $type = self::STRING, private readonly string $delimiter = ',')
    {
    }

    public function transform($value): string
    {
        if (null === $value || [] === $value) {
            return '';
        }

        return implode($this->delimiter, $value);
    }

    public function reverseTransform($value): array
    {
        if (null === $value || u($value)->isEmpty()) {
            return [];
        }

        $array = array_map(
            static fn (AbstractString $part): string => mb_trim($part->toString()),
            u($value)->split($this->delimiter)
        );

        return match ($this->type) {
            self::FLOAT => $this->castAsFloats($array),
            self::INT => $this->castAsIntegers($array),
            default => $array,
        };
    }

    private function castAsFloats(array $array): array
    {
        $floats = [];
        foreach ($array as $value) {
            $floatValue = (float) u($value)->replace(',', '.')->toString();
            if (0.0 === $floatValue && ('0' !== $value && '0.0' !== $value)) {
                $floatValue = $value;
            }

            $floats[] = $floatValue;
        }

        return $floats;
    }

    private function castAsIntegers(array $array): array
    {
        $integers = [];
        foreach ($array as $value) {
            $integerValue = (int) $value;
            if (0 === $integerValue && '0' !== $value) {
                $integerValue = $value;
            }

            $integers[] = $integerValue;
        }

        return $integers;
    }
}
