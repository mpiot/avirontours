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

namespace App\Factory;

use App\Entity\TrainingSplit;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<TrainingSplit>
 */
final class TrainingSplitFactory extends PersistentObjectFactory
{
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'training' => TrainingFactory::new(),
            'duration' => self::faker()->numberBetween(1000, 1400),
            'distance' => 500,
            'strokeRate' => self::faker()->numberBetween(18, 32),
            'averageHeartRate' => self::faker()->numberBetween(120, 180),
            'endingHeartRate' => self::faker()->numberBetween(120, 190),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this;
    }

    #[\Override]
    public static function class(): string
    {
        return TrainingSplit::class;
    }
}
