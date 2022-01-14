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

namespace App\Controller;

use App\Entity\PhysicalQualities;
use App\Repository\LogbookEntryRepository;
use App\Repository\SeasonCategoryRepository;
use App\Service\ArrayNormalizer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class HomepageController extends AbstractController
{
    #[Route(path: '', name: 'homepage')]
    public function homepage(SeasonCategoryRepository $seasonCategoryRepository, LogbookEntryRepository $repository, ChartBuilderInterface $chartBuilder, ArrayNormalizer $normalizer): Response
    {
        if (null === $this->getUser()) {
            return $this->render('homepage/homepage.html.twig', [
                'season_categories' => $seasonCategoryRepository->findAvailableForSubscription(),
            ]);
        }

        // Logbook chart
        $logbookCount = $repository->findStatsByMonth($this->getUser());
        $logbookCount = $normalizer->fillMissingMonths($logbookCount, (new \DateTime('-11 months')), (new \DateTime()), ['distance' => 0, 'session' => 0]);
        $logbookCount = $normalizer->normalize($logbookCount);
        $logbookCount = $normalizer->formatMonthNames($logbookCount);
        $logbookChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $logbookChart->setData([
            'labels' => $logbookCount['months'],
            'datasets' => [
                [
                    'label' => 'Distances',
                    'yAxisID' => 'distances',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                    'data' => $logbookCount['distances'],
                ],
                [
                    'label' => 'Sessions',
                    'yAxisID' => 'sessions',
                    'backgroundColor' => 'rgb(235,54,54, 0.6)',
                    'borderColor' => 'rgb(235,54,54, 1)',
                    'borderWidth' => 1,
                    'data' => $logbookCount['sessions'],
                ],
            ],
        ]);
        $logbookChart->setOptions([
            'scales' => [
                'distances' => [
                    'type' => 'linear',
                    'position' => 'left',
                    'ticks' => [
                        'beginAtZero' => true,
                        'precision' => 0,
                    ],
                ],
                'sessions' => [
                    'type' => 'linear',
                    'position' => 'right',
                    'ticks' => [
                        'beginAtZero' => true,
                        'precision' => 0,
                    ],
                ],
            ],
        ]);

        // Physical qualities chart
        /** @var PhysicalQualities $physicalQualities */
        $physicalQualities = $this->getUser()->getPhysicalQualities();
        if (null !== $physicalQualities) {
            $physicalQualitiesChart = $chartBuilder->createChart(Chart::TYPE_RADAR);
            $physicalQualitiesChart->setData([
                'labels' => ['Proprioception', 'Poids/Puissance', 'Force explosive', 'Force d\'endurance', 'Force maximale', 'Résistance', 'Gainage', 'Souplesse', 'Récupération'],
                'datasets' => [
                    [
                        'label' => '',
                        'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                        'borderColor' => 'rgba(54, 162, 235, 1)',
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
            $physicalQualitiesChart->setOptions([
                'legend' => [
                    'display' => false,
                ],
                'scale' => [
                    'ticks' => [
                        'suggestedMin' => 0,
                        'suggestedMax' => 20,
                    ],
                ],
            ]);
        }

        return $this->render('homepage/dashboard.html.twig', [
            'logbookChart' => $logbookChart,
            'physicalQualitiesChart' => $physicalQualitiesChart ?? null,
        ]);
    }
}
