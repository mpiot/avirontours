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

namespace App\Tests\Service\Fit;

use App\Service\Fit\FitDeviceLabel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sportlog\FIT\Profile\Types\GarminProduct;
use Sportlog\FIT\Profile\Types\Manufacturer;

class FitDeviceLabelTest extends TestCase
{
    #[DataProvider('provideDevices')]
    public function testTheLabelNamesTheBrandAndTheModel(?int $manufacturer, ?int $product, ?string $productName, ?string $expected): void
    {
        self::assertSame($expected, FitDeviceLabel::from($manufacturer, $product, $productName));
    }

    public static function provideDevices(): iterable
    {
        yield 'garmin model by number' => [Manufacturer::GARMIN, GarminProduct::FR255, null, 'Garmin FR255'];
        yield 'garmin unknown number' => [Manufacturer::GARMIN, 999999, null, 'Garmin'];
        yield 'polar names itself' => [Manufacturer::POLAR_ELECTRO, 203, 'Polar Vantage V', 'Polar Vantage V'];
        yield 'concept2 logbook export' => [Manufacturer::CONCEPT2, 0, null, 'Concept2'];
        yield 'nk without a product name' => [Manufacturer::NIELSEN_KELLERMAN, 0, null, 'NK'];
        yield 'brand and product name' => [Manufacturer::NIELSEN_KELLERMAN, 0, 'SpeedCoach GPS 2', 'NK SpeedCoach GPS 2'];
        yield 'unknown brand with a name' => [999, 1, 'Some Watch', 'Some Watch'];
        yield 'nothing known' => [999, 1, '  ', null];
        yield 'no file_id data' => [null, null, null, null];
    }
}
