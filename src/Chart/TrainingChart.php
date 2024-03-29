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

namespace App\Chart;

use App\Entity\Training;
use App\Entity\User;
use App\Repository\TrainingRepository;
use App\Service\TrainingCalculator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class TrainingChart
{
    public function __construct(private TrainingRepository $trainingRepository, private ChartBuilderInterface $chartBuilder)
    {
    }

    public function pathways(User $user): ?Chart
    {
        $from = (new \DateTime('-1 month'))->setTime(0, 0);
        $to = (new \DateTime('now'))->setTime(23, 59);

        /** @var Collection|Training[] $trainings */
        $trainings = new ArrayCollection($this->trainingRepository->findForUser($user, $from, $to));

        if ($trainings->isEmpty()) {
            return null;
        }

        $totalDuration = TrainingCalculator::getDuration($trainings);
        $aerobicRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => Training::ENERGY_PATHWAY_AEROBIC === $training->getEnergyPathway())) / $totalDuration;
        $thresoldRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => Training::ENERGY_PATHWAY_THRESHOLD === $training->getEnergyPathway())) / $totalDuration;
        $lacticRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => Training::ENERGY_PATHWAY_LACTIC_ANAEROBIC === $training->getEnergyPathway())) / $totalDuration;
        $alacticRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => Training::ENERGY_PATHWAY_ALACTIC_ANAEROBIC === $training->getEnergyPathway())) / $totalDuration;

        $chart = $this->chartBuilder->createChart(Chart::TYPE_PIE);
        $chart->setData([
            'labels' => ['Aérobie', 'Transition aérobie/anaérobie', 'Anaérobie lactique', 'Anaérobie alactique'],
            'datasets' => [
                [
                    'label' => '',
                    'data' => [
                        $aerobicRatio * 100,
                        $thresoldRatio * 100,
                        $lacticRatio * 100,
                        $alacticRatio * 100,
                    ],
                    'backgroundColor' => [
                        'rgb(106, 204, 43)',
                        'rgb(249, 191, 28)',
                        'rgb(222, 15, 91)',
                        'rgb(176, 42, 55)',
                    ],
                ],
            ],
        ]);

        return $chart;
    }

    public function sports(User $user): ?Chart
    {
        $from = (new \DateTime('-1 month'))->setTime(0, 0);
        $to = (new \DateTime('now'))->setTime(23, 59);

        /** @var Collection|Training[] $trainings */
        $trainings = new ArrayCollection($this->trainingRepository->findForUser($user, $from, $to));

        if ($trainings->isEmpty()) {
            return null;
        }

        $totalDuration = TrainingCalculator::getDuration($trainings);
        $rowingTraingsRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => Training::SPORT_ROWING === $training->getSport())) / $totalDuration;
        $ergometerTrainingsRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => Training::SPORT_ERGOMETER === $training->getSport())) / $totalDuration;
        $workoutTrainingsRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => \in_array($training->getSport(), [Training::SPORT_WORKOUT_ENDURANCE, Training::SPORT_WORKOUT_STRENGTH], true))) / $totalDuration;
        $otherSportTraingsRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => false === \in_array($training->getSport(), [Training::SPORT_ROWING, Training::SPORT_ERGOMETER, Training::SPORT_WORKOUT_ENDURANCE, Training::SPORT_WORKOUT_STRENGTH], true))) / $totalDuration;

        $chart = $this->chartBuilder->createChart(Chart::TYPE_PIE);
        $chart->setData([
            'labels' => ['Aviron', 'Ergomètre', 'Musculation', 'Autres'],
            'datasets' => [
                [
                    'label' => '',
                    'data' => [
                        $rowingTraingsRatio * 100,
                        $ergometerTrainingsRatio * 100,
                        $workoutTrainingsRatio * 100,
                        $otherSportTraingsRatio * 100,
                    ],
                    'backgroundColor' => [
                        'rgb(70, 199, 238)',
                        'rgb(249, 191, 28)',
                        'rgb(176, 42, 55)',
                        'rgb(194, 202, 202)',
                    ],
                ],
            ],
        ]);

        return $chart;
    }
}
