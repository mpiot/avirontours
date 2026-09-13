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

namespace App\Tests\Enum;

use App\Enum\TrainingSource;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TrainingSourceTest extends TestCase
{
    #[DataProvider('provideLocks')]
    public function testOnlyImportedSourcesLockTheMeasures(TrainingSource $source, bool $locks): void
    {
        self::assertSame($locks, $source->locksMeasures());
    }

    public function testEverySourceHasItsLockTested(): void
    {
        self::assertCount(\count(TrainingSource::cases()), iterator_to_array(self::provideLocks()));
    }

    public static function provideLocks(): iterable
    {
        yield 'manual' => [TrainingSource::Manual, false];
        yield 'logbook' => [TrainingSource::Logbook, false];
        yield 'concept2' => [TrainingSource::Concept2, true];
        yield 'fit' => [TrainingSource::Fit, true];
    }
}
