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

use App\Entity\Training;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method Training|null find($id, $lockMode = null, $lockVersion = null)
 * @method Training|null findOneBy(array $criteria, array $orderBy = null)
 * @method Training[]    findAll()
 * @method Training[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<Training>
 *
 * @psalm-method list<Training> findAll()
 * @psalm-method list<Training> findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Training::class);
    }

    public function findUserPaginated(User $user, $page = 1): PaginationInterface
    {
        $query = $this->createQueryBuilder('training')
            ->innerJoin('training.user', 'user')
            ->where('user.id = :user')
            ->orderBy('training.trainedAt', 'DESC')
            ->setParameter('user', $user->getId())
            ->getQuery()
        ;

        return $this->paginator->paginate(
            $query,
            $page,
            Training::NUM_ITEMS
        );
    }

    public function findForUser(?User $user = null, \DateTime $from = null, \DateTime $to = null): array
    {
        $query = $this->createQueryBuilder('training')
            ->innerJoin('training.user', 'user')
            ->where('user.id = :user_id')
            ->andWhere('training.trainedAt BETWEEN :from AND :to')
            ->setParameters([
                'user_id' => $user->getId(),
                'from' => $from,
                'to' => $to,
            ])
            ->getQuery()
        ;

        return $query->getResult();
    }
}
