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
use function Symfony\Component\String\u;

class NonUserCrewMemberArrayToStringTransformer implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function transform($nonUserCrewMembers): string
    {
        /* @var string[] $nonUserCrewMembers */
        return implode(',', $nonUserCrewMembers);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($string): array
    {
        if (null === $string || u($string)->isEmpty()) {
            return [];
        }

        return array_filter(array_unique(array_map('trim', u($string)->split(','))));
    }
}
