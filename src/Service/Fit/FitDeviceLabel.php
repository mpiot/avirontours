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

namespace App\Service\Fit;

use Sportlog\FIT\Profile\Types\GarminProduct;
use Sportlog\FIT\Profile\Types\Manufacturer;

/**
 * "Garmin FR255", "Polar Vantage V", "NK": the FIT profile only knows brands and Garmin models by number.
 */
final class FitDeviceLabel
{
    private const array MANUFACTURERS = [
        Manufacturer::GARMIN => 'Garmin',
        Manufacturer::CONCEPT2 => 'Concept2',
        Manufacturer::NIELSEN_KELLERMAN => 'NK',
        Manufacturer::POLAR_ELECTRO => 'Polar',
        Manufacturer::SUUNTO => 'Suunto',
        Manufacturer::COROS => 'Coros',
        Manufacturer::WAHOO_FITNESS => 'Wahoo',
    ];

    public static function from(?int $manufacturer, ?int $product, ?string $productName): ?string
    {
        $brand = self::brand($manufacturer);
        $model = self::model($manufacturer, $product, $productName);

        // Unknown brand: device name or nothing
        if (null === $brand) {
            return $model;
        }

        // "Concept2", "NK"
        if (null === $model) {
            return $brand;
        }

        // Polar writes the brand in product_name already: do not say it twice.
        // "Polar Vantage V", no "Polar Polar Vantage V"
        if (str_starts_with(mb_strtolower($model), mb_strtolower($brand))) {
            return $model;
        }

        // "Garmin FR255"
        return "{$brand} {$model}";
    }

    private static function brand(?int $manufacturer): ?string
    {
        return null === $manufacturer ? null : (self::MANUFACTURERS[$manufacturer] ?? null);
    }

    private static function model(?int $manufacturer, ?int $product, ?string $productName): ?string
    {
        // The device's own words win: Polar writes "Polar Vantage V" there.
        if (null !== $productName && '' !== mb_trim($productName)) {
            return mb_trim($productName);
        }

        // Garmin writes no product_name, but the FIT profile names its models (FR255 = 3992).
        if (Manufacturer::GARMIN === $manufacturer && null !== $product) {
            $garminProductConstants = new \ReflectionClass(GarminProduct::class)->getConstants();
            $constant = array_search($product, $garminProductConstants, true);

            return false === $constant ? null : str_replace('_', ' ', $constant);
        }

        return null;
    }
}
