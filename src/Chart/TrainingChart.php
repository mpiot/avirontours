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
use App\Enum\SportType;
use App\Repository\TrainingRepository;
use App\Service\TrainingCalculator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

final class TrainingChart
{
    public function __construct(
        private readonly TrainingRepository $trainingRepository,
        private readonly ChartBuilderInterface $chartBuilder,
    ) {
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
        $rowingTrainingsRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => SportType::Rowing === $training->getSport())) / $totalDuration;
        $ergometerTrainingsRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => SportType::Ergometer === $training->getSport())) / $totalDuration;
        $weightTrainingsRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => SportType::WeightTraining === $training->getSport())) / $totalDuration;
        $strengtheningTrainingsRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => SportType::Strengthening === $training->getSport())) / $totalDuration;
        $otherSportTrainingsRatio = TrainingCalculator::getDuration($trainings->filter(fn (Training $training) => false === \in_array($training->getSport(), [SportType::Rowing, SportType::Ergometer, SportType::WeightTraining, SportType::Strengthening], true))) / $totalDuration;

        $chart = $this->chartBuilder->createChart(Chart::TYPE_PIE);
        $chart->setData([
            'labels' => [
                SportType::Rowing->label(),
                SportType::Ergometer->label(),
                SportType::WeightTraining->label(),
                SportType::Strengthening->label(),
                SportType::Other->label(),
            ],
            'datasets' => [
                [
                    'label' => '',
                    'data' => [
                        $rowingTrainingsRatio * 100,
                        $ergometerTrainingsRatio * 100,
                        $weightTrainingsRatio * 100,
                        $strengtheningTrainingsRatio * 100,
                        $otherSportTrainingsRatio * 100,
                    ],
                    'backgroundColor' => [
                        SportType::Rowing->color(),
                        SportType::Ergometer->color(),
                        SportType::WeightTraining->color(),
                        SportType::Strengthening->color(),
                        SportType::Other->color(),
                    ],
                ],
            ],
        ]);
        $chart->setOptions([
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
        ]);

        return $chart;
    }
}
