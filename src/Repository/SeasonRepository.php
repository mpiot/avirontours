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

use App\Entity\Season;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Season|null find($id, $lockMode = null, $lockVersion = null)
 * @method Season|null findOneBy(array $criteria, array $orderBy = null)
 * @method Season[]    findAll()
 * @method Season[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<Season>
 */
class SeasonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Season::class);
    }

    public function findRenewSeason(User $user)
    {
        $queryBuilder = $this->createQueryBuilder('season');
        $queryBuilder
            ->innerJoin('season.seasonCategories', 'season_categories')->addSelect('season_categories')
            ->andWhere('season.subscriptionEnabled = true')
            ->andWhere('season_categories.displayed = true')
            ->orderBy('season.name', 'DESC')
            ->getQuery()
            ->setMaxResults(1)
        ;

        $unavailableSeasons = $this->findUnavailableSeasonForUser($user);
        if (!empty($unavailableSeasons)) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->notIn('season.id', ':unavailableSeasons'))
                ->setParameter('unavailableSeasons', $unavailableSeasons)
            ;
        }

        return new Paginator($queryBuilder->getQuery());
    }

    public function findUnavailableSeasonForUser(User $user)
    {
        $query = $this->createQueryBuilder('unavailable_season')
            ->select(['unavailable_season.id'])
            ->innerJoin('unavailable_season.seasonCategories', 'season_categories')
            ->innerJoin('season_categories.licenses', 'licenses')
            ->innerJoin('licenses.user', 'user')
            ->where('user.id = :user')
            ->setParameter('user', $user->getId())
            ->getQuery()
        ;

        return $query->getArrayResult();
    }
}
