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

use App\Entity\SeasonUser;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SeasonUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method SeasonUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method SeasonUser[]    findAll()
 * @method SeasonUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeasonUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeasonUser::class);
    }

    public function findLastUserSeason(User $user)
    {
        $query = $this->createQueryBuilder('user_season')
            ->innerJoin('user_season.user', 'user')
            ->innerJoin('user_season.season', 'season')->addSelect('season')
            ->where('user = :user')
            ->orderBy('season.name', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->setMaxResults(1);

        return $query->getOneOrNullResult();
    }
}
