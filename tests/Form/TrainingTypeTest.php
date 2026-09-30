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

namespace App\Tests\Form;

use App\Entity\Training;
use App\Entity\User;
use App\Enum\SportType;
use App\Form\TrainingType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;

class TrainingTypeTest extends TestCase
{
    #[DataProvider('provideDistances')]
    public function testDistanceIsEnteredInTheUnitOfTheSport(SportType $sport, ?string $distance, ?int $meters): void
    {
        $training = (new Training(new User()))->setDistance(5000);
        $form = Forms::createFormFactory()->create(TrainingType::class, $training);

        $form->submit(['sport' => $sport->value, 'distance' => $distance]);

        self::assertSame(null !== $distance, $form->has('distance'));
        self::assertSame($meters, $training->getDistance());
    }

    public function testEverySportHasItsDistanceTested(): void
    {
        self::assertCount(\count(SportType::cases()), iterator_to_array(self::provideDistances()));
    }

    public static function provideDistances(): iterable
    {
        yield 'cycling' => [SportType::Cycling, '12.5', 12500];
        yield 'ergometer' => [SportType::Ergometer, '10000', 10000];
        yield 'general physical preparation' => [SportType::GeneralPhysicalPreparation, null, null];
        yield 'other' => [SportType::Other, '12.5', 12500];
        yield 'rowing' => [SportType::Rowing, '12.5', 12500];
        yield 'running' => [SportType::Running, '12.5', 12500];
        yield 'strengthening' => [SportType::Strengthening, null, null];
        yield 'swimming' => [SportType::Swimming, '1500', 1500];
        yield 'weight training' => [SportType::WeightTraining, null, null];
        yield 'yoga' => [SportType::Yoga, null, null];
    }
}
