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

namespace App\Chart;

use App\Entity\User;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final readonly class PhysicalQualitiesChart
{
    public function __construct(private ChartBuilderInterface $chartBuilder)
    {
    }

    public function chart(User $user): ?Chart
    {
        $physicalQualities = $user->getPhysicalQualities();

        if (null === $physicalQualities) {
            return null;
        }

        $chart = $this->chartBuilder->createChart(Chart::TYPE_RADAR);
        $chart->setData([
            'labels' => ['Proprioception', 'Poids/Puissance', 'Force explosive', "Force d'endurance", 'Force maximale', 'Résistance', 'Gainage', 'Souplesse', 'Récupération'],
            'datasets' => [
                [
                    'label' => '',
                    'backgroundColor' => ChartPalette::translucent(ChartPalette::INDIGO, 0.35),
                    'borderColor' => ChartPalette::INDIGO,
                    'data' => [
                        $physicalQualities->getProprioception(),
                        $physicalQualities->getWeightPowerRatio(),
                        $physicalQualities->getExplosiveStrength(),
                        $physicalQualities->getEnduranceStrength(),
                        $physicalQualities->getMaximumStrength(),
                        $physicalQualities->getStressResistance(),
                        $physicalQualities->getCoreStrength(),
                        $physicalQualities->getFlexibility(),
                        $physicalQualities->getRecovery(),
                    ],
                ],
            ],
        ]);

        $chart->setOptions([
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'r' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 20,
                ],
            ],
        ]);

        return $chart;
    }
}
