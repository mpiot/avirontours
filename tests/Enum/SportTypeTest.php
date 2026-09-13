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

use App\Enum\SportSpecificity;
use App\Enum\SportType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sportlog\FIT\Profile\Types\Sport;
use Sportlog\FIT\Profile\Types\SubSport;

class SportTypeTest extends TestCase
{
    #[DataProvider('provideSpeeds')]
    public function testFormatSpeedUsesTheUnitOfTheDiscipline(SportType $sport, ?string $unit, ?string $speed): void
    {
        self::assertSame($unit, $sport->speedUnit());
        self::assertSame($speed, $sport->formatSpeed(36000, 10000));
    }

    #[DataProvider('provideSpeedLabels')]
    public function testASportNamesItsSpeedAfterTheUnitItUses(SportType $sport, string $label): void
    {
        self::assertSame($label, $sport->speedLabel());
    }

    public function testEverySportHasItsSpeedLabelTested(): void
    {
        self::assertCount(\count(SportType::cases()), iterator_to_array(self::provideSpeedLabels()));
    }

    #[DataProvider('provideTrackWatts')]
    public function testWattsAreAnErgometerFigure(SportType $sport, bool $trackWatt): void
    {
        self::assertSame($trackWatt, $sport->tracksWatts());
    }

    #[DataProvider('provideIncompleteSpeedData')]
    public function testFormatSpeedNeedADurationAndADistance(SportType $sport, ?int $duration, ?int $distance): void
    {
        self::assertNull($sport->formatSpeed($duration, $distance));
    }

    #[DataProvider('provideSpecificities')]
    public function testSportsSpecificities(SportType $sport, SportSpecificity $specificity): void
    {
        self::assertSame($specificity, $sport->specificity());
    }

    public function testEverySportHasItsSpecificityTested(): void
    {
        self::assertCount(\count(SportType::cases()), iterator_to_array(self::provideSpecificities()));
    }

    #[DataProvider('provideFitSports')]
    public function testEachFitSportAndSubSportLandsOnAClubSport(?int $sport, ?int $subSport, SportType $expected): void
    {
        self::assertSame($expected, SportType::fromFit($sport, $subSport));
    }

    public static function provideSpeeds(): iterable
    {
        yield 'cycling' => [SportType::Cycling, 'km/h', '10,0'];
        yield 'ergometer' => [SportType::Ergometer, '/500m', '03:00.0'];
        yield 'general physical preparation' => [SportType::GeneralPhysicalPreparation, null, null];
        yield 'other' => [SportType::Other, null, null];
        yield 'rowing' => [SportType::Rowing, '/500m', '03:00.0'];
        yield 'running' => [SportType::Running, '/km', '06:00'];
        yield 'strengthening' => [SportType::Strengthening, null, null];
        yield 'swimming' => [SportType::Swimming, '/100m', '00:36'];
        yield 'weight training' => [SportType::WeightTraining, null, null];
        yield 'yoga' => [SportType::Yoga, null, null];
    }

    public static function provideSpeedLabels(): iterable
    {
        yield 'cycling' => [SportType::Cycling, 'Vitesse'];
        yield 'ergometer' => [SportType::Ergometer, 'Allure'];
        yield 'general physical preparation' => [SportType::GeneralPhysicalPreparation, 'Allure'];
        yield 'other' => [SportType::Other, 'Allure'];
        yield 'rowing' => [SportType::Rowing, 'Allure'];
        yield 'running' => [SportType::Running, 'Allure'];
        yield 'strengthening' => [SportType::Strengthening, 'Allure'];
        yield 'swimming' => [SportType::Swimming, 'Allure'];
        yield 'weight training' => [SportType::WeightTraining, 'Allure'];
        yield 'yoga' => [SportType::Yoga, 'Allure'];
    }

    public static function provideTrackWatts(): iterable
    {
        yield 'cycling' => [SportType::Cycling, false];
        yield 'ergometer' => [SportType::Ergometer, true];
        yield 'general physical preparation' => [SportType::GeneralPhysicalPreparation, false];
        yield 'other' => [SportType::Other, false];
        yield 'rowing' => [SportType::Rowing, false];
        yield 'running' => [SportType::Running, false];
        yield 'strengthening' => [SportType::Strengthening, false];
        yield 'swimming' => [SportType::Swimming, false];
        yield 'weight training' => [SportType::WeightTraining, false];
        yield 'yoga' => [SportType::Yoga, false];
    }

    public static function provideFitSports(): iterable
    {
        yield 'garmin erg piece' => [Sport::ROWING, SubSport::INDOOR_ROWING, SportType::Ergometer];
        yield 'concept2 erg piece' => [Sport::FITNESS_EQUIPMENT, SubSport::INDOOR_ROWING, SportType::Ergometer];
        yield 'polar on-water rowing' => [Sport::ROWING, SubSport::GENERIC, SportType::Rowing];
        yield 'nk rowing without sub sport' => [Sport::ROWING, null, SportType::Rowing];
        yield 'running' => [Sport::RUNNING, SubSport::GENERIC, SportType::Running];
        yield 'treadmill' => [Sport::RUNNING, SubSport::TREADMILL, SportType::Running];
        yield 'trail' => [Sport::RUNNING, SubSport::TRAIL, SportType::Running];
        yield 'cycling' => [Sport::CYCLING, SubSport::GENERIC, SportType::Cycling];
        yield 'e-biking' => [Sport::E_BIKING, SubSport::GENERIC, SportType::Cycling];
        yield 'polar indoor cycling' => [Sport::CYCLING, SubSport::INDOOR_CYCLING, SportType::Cycling];
        yield 'spinning' => [Sport::FITNESS_EQUIPMENT, SubSport::SPIN, SportType::Cycling];
        yield 'pool swimming' => [Sport::SWIMMING, SubSport::LAP_SWIMMING, SportType::Swimming];
        yield 'open water' => [Sport::SWIMMING, SubSport::OPEN_WATER, SportType::Swimming];
        yield 'yoga' => [Sport::TRAINING, SubSport::YOGA, SportType::Yoga];
        yield 'pilates' => [Sport::TRAINING, SubSport::PILATES, SportType::Yoga];
        yield 'strength training' => [Sport::TRAINING, SubSport::STRENGTH_TRAINING, SportType::WeightTraining];
        yield 'cardio training' => [Sport::TRAINING, SubSport::CARDIO_TRAINING, SportType::GeneralPhysicalPreparation];
        yield 'flexibility' => [Sport::TRAINING, SubSport::FLEXIBILITY_TRAINING, SportType::GeneralPhysicalPreparation];
        yield 'hiit' => [Sport::HIIT, SubSport::GENERIC, SportType::GeneralPhysicalPreparation];
        yield 'walking' => [Sport::WALKING, SubSport::GENERIC, SportType::Other];
        yield 'multisport' => [Sport::MULTISPORT, SubSport::GENERIC, SportType::Other];
        yield 'unknown sport' => [null, null, SportType::Other];
    }

    public static function provideSpecificities(): iterable
    {
        yield 'cycling' => [SportType::Cycling, SportSpecificity::NonSpecific];
        yield 'ergometer' => [SportType::Ergometer, SportSpecificity::SemiSpecific];
        yield 'general physical preparation' => [SportType::GeneralPhysicalPreparation, SportSpecificity::NonSpecific];
        yield 'other' => [SportType::Other, SportSpecificity::NonSpecific];
        yield 'rowing' => [SportType::Rowing, SportSpecificity::Specific];
        yield 'running' => [SportType::Running, SportSpecificity::NonSpecific];
        yield 'strengthening' => [SportType::Strengthening, SportSpecificity::NonSpecific];
        yield 'swimming' => [SportType::Swimming, SportSpecificity::NonSpecific];
        yield 'weight training' => [SportType::WeightTraining, SportSpecificity::NonSpecific];
        yield 'yoga' => [SportType::Yoga, SportSpecificity::NonSpecific];
    }

    public static function provideIncompleteSpeedData(): iterable
    {
        $incomplete = [
            'no duration' => [null, 10000],
            'no distance' => [36000, null],
            'zero distance' => [36000, 0],
            'no duration nor distance' => [null, null],
            'no duration, zero distance' => [null, 0],
        ];

        foreach (SportType::cases() as $sport) {
            foreach ($incomplete as $label => [$duration, $distance]) {
                yield "{$sport->value}, {$label}" => [$sport, $duration, $distance];
            }
        }
    }
}
