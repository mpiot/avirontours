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

use App\Entity\TrainingPhase;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<TrainingPhase>
 */
final class TrainingPhaseFactory extends PersistentObjectFactory
{
    public function withoutSeries(): static
    {
        return $this->with([
            'times' => null,
            'distances' => null,
            'paces' => null,
            'strokeRates' => null,
        ]);
    }

    #[\Override]
    protected function defaults(): array|callable
    {
        $strokes = range(1, self::faker()->numberBetween(10, 30));

        return [
            'training' => TrainingFactory::new(),
            'duration' => self::faker()->numberBetween(3000, 12000),
            'distance' => self::faker()->numberBetween(1000, 4000),
            'times' => array_map(static fn (int $n): int => $n * 600, $strokes),
            'distances' => array_map(static fn (int $n): int => $n * 200, $strokes),
            'paces' => array_map(static fn (int $n): int => self::faker()->numberBetween(1000, 1400), $strokes),
            'strokeRates' => array_map(static fn (int $n): int => self::faker()->numberBetween(18, 32), $strokes),
            'strokeRate' => self::faker()->numberBetween(18, 32),
            'averageHeartRate' => self::faker()->numberBetween(120, 160),
            'maxHeartRate' => self::faker()->numberBetween(160, 200),
            'endingHeartRate' => self::faker()->numberBetween(120, 180),
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
        return TrainingPhase::class;
    }
}
