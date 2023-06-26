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

use App\Entity\Physiology;
use App\Repository\PhysiologyRepository;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static Physiology|Proxy                     createOne(array $attributes = [])
 * @method static Physiology[]|Proxy[]                 createMany(int $number, $attributes = [])
 * @method static Physiology|Proxy                     findOrCreate(array $attributes)
 * @method static Physiology|Proxy                     random(array $attributes = [])
 * @method static Physiology|Proxy                     randomOrCreate(array $attributes = [])
 * @method static Physiology[]|Proxy[]                 randomSet(int $number, array $attributes = [])
 * @method static Physiology[]|Proxy[]                 randomRange(int $min, int $max, array $attributes = [])
 * @method static PhysiologyRepository|RepositoryProxy repository()
 * @method        Physiology|Proxy                     create($attributes = [])
 */
final class PhysiologyFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        $max = self::faker()->numberBetween(180, 2150);

        return [
            'user' => UserFactory::new(),
            'lightAerobicHeartRateMin' => round($max * 0.65),
            'heavyAerobicHeartRateMin' => round($max * 0.70),
            'anaerobicThresholdHeartRateMin' => round($max * 0.80),
            'oxygenTransportationHeartRateMin' => round($max * 0.85),
            'anaerobicHeartRateMin' => round($max * 0.95),
            'maximumHeartRate' => $max,
            'maximumOxygenConsumption' => self::faker()->randomFloat(2, 20, 90),
        ];
    }

    protected function initialize(): self
    {
        // see https://github.com/zenstruck/foundry#initialization
        return $this;
        // ->afterInstantiate(function(Physiology $physiology) {})
    }

    protected static function getClass(): string
    {
        return Physiology::class;
    }
}
