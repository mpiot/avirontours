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

namespace App\Repository;

use App\Entity\WorkoutMaximumLoad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WorkoutMaximumLoad|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkoutMaximumLoad|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkoutMaximumLoad[]    findAll()
 * @method WorkoutMaximumLoad[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<WorkoutMaximumLoad>
 *
 * @psalm-method list<WorkoutMaximumLoad> findAll()
 * @psalm-method list<WorkoutMaximumLoad> findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkoutMaximumLoadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkoutMaximumLoad::class);
    }
}
