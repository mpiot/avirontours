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

namespace App\Repository;

use App\Entity\PlannedTraining;
use App\Entity\TrainingPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlannedTraining>
 */
class PlannedTrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlannedTraining::class);
    }

    /**
     * @return PlannedTraining[]
     */
    public function findMonthTrainings(TrainingPlan $trainingPlan, int $year, int $month): array
    {
        $currentMonth = new \DateTimeImmutable("{$year}-{$month}-01");
        $firstDayOfTheMonth = $currentMonth->modify('first day of this month midnight');
        $lastDayOfTheMonth = $currentMonth->modify('last day of this month midnight');

        $em = $this->getEntityManager();

        return $em->createQuery(/* @lang DQL */ '
                SELECT planned_training
                FROM App\Entity\PlannedTraining planned_training
                INNER JOIN planned_training.trainingPlan training_plan
                WHERE training_plan = :training_plan
                AND planned_training.date BETWEEN :start AND :end
            ')
            ->setParameters([
                'training_plan' => $trainingPlan,
                'start' => $firstDayOfTheMonth,
                'end' => $lastDayOfTheMonth,
            ])
            ->getResult()
        ;
    }
}
