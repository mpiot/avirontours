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

use App\Chart\LogbookChart;
use App\Chart\PhysicalQualitiesChart;
use App\Chart\TrainingChart;
use App\Repository\SeasonCategoryRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{
    #[Route(path: '', name: 'homepage')]
    public function homepage(SeasonCategoryRepository $seasonCategoryRepository, LogbookChart $logbookChart, PhysicalQualitiesChart $physicalQualitiesChart, TrainingChart $trainingsChart): Response
    {
        if (null === $this->getUser()) {
            return $this->render('homepage/homepage.html.twig', [
                'season_categories' => $seasonCategoryRepository->findAvailableForSubscription(),
            ]);
        }

        return $this->render('homepage/dashboard.html.twig', [
            'logbookChart' => $logbookChart->chart($this->getUser()),
            'physicalQualitiesChart' => $physicalQualitiesChart->chart($this->getUser()),
            'trainingsPathwaysChart' => $trainingsChart->pathways($this->getUser()),
            'trainingsSportsChart' => $trainingsChart->sports($this->getUser()),
        ]);
    }
}
