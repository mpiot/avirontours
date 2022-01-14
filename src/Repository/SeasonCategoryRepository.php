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
use App\Entity\SeasonCategory;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SeasonCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method SeasonCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method SeasonCategory[]    findAll()
 * @method SeasonCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @extends ServiceEntityRepository<SeasonCategory>
 * @psalm-method list<SeasonCategory> findAll()
 * @psalm-method list<SeasonCategory> findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeasonCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeasonCategory::class);
    }

    public function findSubscriptionSeasonCategory(string $slug, User $user = null)
    {
        $queryBuilder = $this->createQueryBuilder('season_category')
            ->innerJoin('season_category.season', 'season')->addSelect('season')
            ->andWhere('season_category.slug = :slug')
            ->andWhere('season.subscriptionEnabled = true')
            ->andWhere('season_category.displayed = true')
            ->setParameter('slug', $slug)
        ;

        if (null !== $user) {
            $unavailableSeasons = $this->getEntityManager()->getRepository(Season::class)->findUnavailableSeasonForUser($user);
            if (!empty($unavailableSeasons)) {
                $queryBuilder
                    ->andWhere($queryBuilder->expr()->notIn('season.id', ':unavailableSeasons'))
                    ->setParameter('unavailableSeasons', $unavailableSeasons)
                ;
            }
        }

        return $queryBuilder->getQuery()->getOneOrNullResult();
    }

    public function findAvailableForSubscription(): array
    {
        $query = $this->createQueryBuilder('season_category')
            ->innerJoin('season_category.season', 'season')->addSelect('season')
            ->andWhere('season.subscriptionEnabled = true')
            ->andWhere('season_category.displayed = true')
            ->getQuery()
        ;

        return $query->getResult();
    }
}
