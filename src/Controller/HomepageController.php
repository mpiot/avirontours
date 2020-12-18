<?php

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

use App\Repository\LogbookEntryRepository;
use App\Service\ArrayNormalizer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class HomepageController extends AbstractController
{
    /**
     * @Route("", name="homepage")
     * @Security("is_granted('ROLE_USER')")
     */
    public function homepage(LogbookEntryRepository $repository, ChartBuilderInterface $chartBuilder, ArrayNormalizer $normalizer)
    {
        $count = $repository->findStatsByMonth($this->getUser());
        $count = $normalizer->fillMissing($count, (new \DateTime('-11 months'))->format('m'), (new \DateTime())->format('m'), ['distance' => 0, 'session' => 0], 'month');
        $count = $normalizer->normalize($count);
        $count = $normalizer->formatMonthNames($count);

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => $count['months'],
            'datasets' => [
                [
                    'label' => 'Distances',
                    'yAxisId' => 'distances',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                    'data' => $count['distances'],
                ],
                [
                    'label' => 'Sessions',
                    'yAxisId' => 'sessions',
                    'backgroundColor' => 'rgb(235,54,54, 0.6)',
                    'borderColor' => 'rgb(235,54,54, 1)',
                    'borderWidth' => 1,
                    'data' => $count['sessions'],
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [
                'yAxes' => [
                    [
                        'id' => 'distances',
                        'type' => 'linear',
                        'position' => 'left',
                        'ticks' => [
                            'beginAtZero' => true,
                            'precision' => 0,
                        ],
                    ],
                    [
                        'id' => 'sessions',
                        'type' => 'linear',
                        'position' => 'right',
                        'ticks' => [
                            'beginAtZero' => true,
                            'precision' => 0,
                        ],
                    ],
                ],
            ],
        ]);

        return $this->render('homepage/homepage.html.twig', [
            'chart' => $chart,
        ]);
    }
}
