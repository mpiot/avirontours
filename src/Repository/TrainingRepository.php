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
use App\Enum\SportType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

use function Symfony\Component\String\u;

/**
 * @method Training|null find($id, $lockMode = null, $lockVersion = null)
 * @method Training|null findOneBy(array $criteria, array $orderBy = null)
 * @method Training[]    findAll()
 * @method Training[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<Training>
 */
class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly PaginatorInterface $paginator)
    {
        parent::__construct($registry, Training::class);
    }

    public function findUserPaginated(
        User $user,
        int $page = 1,
        ?\DateTimeInterface $from = null,
        ?\DateTimeInterface $to = null,
        ?SportType $sport = null,
        ?string $query = null,
    ): PaginationInterface {
        $queryBuilder = $this->createQueryBuilder('training')
            ->where('training.user = :user')
            ->orderBy('training.trainedAt', 'DESC')
            ->setParameter('user', $user)
        ;

        if (null !== $from) {
            $queryBuilder->andWhere('training.trainedAt >= :from')->setParameter('from', $from);
        }

        if (null !== $to) {
            $queryBuilder->andWhere('training.trainedAt <= :to')->setParameter('to', $to);
        }

        if (null !== $sport) {
            $queryBuilder->andWhere('training.sport = :sport')->setParameter('sport', $sport);
        }

        $term = null !== $query ? u($query)->trim()->lower()->toString() : '';
        if ('' !== $term) {
            $queryBuilder
                ->andWhere('LOWER(training.comment) LIKE :query')
                ->setParameter('query', "%{$term}%")
            ;
        }

        return $this->paginator->paginate(
            $queryBuilder->getQuery(),
            $page,
            Training::NUM_ITEMS
        );
    }

    /**
     * @return Training[]
     */
    /**
     * @return list<Training> oldest first
     */
    public function findRatedForUser(User $user, \DateTimeInterface $to): array
    {
        return $this->createQueryBuilder('training')
            ->where('training.user = :user')
            ->andWhere('training.ratedPerceivedExertion IS NOT NULL')
            ->andWhere('training.trainedAt <= :to')
            ->orderBy('training.trainedAt', 'ASC')
            ->setParameter('user', $user)
            ->setParameter('to', $to)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findForUser(?User $user = null, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $query = $this->createQueryBuilder('training')
            ->innerJoin('training.user', 'user')
            ->where('user.id = :user_id')
            ->andWhere('training.trainedAt BETWEEN :from AND :to')
            ->orderBy('training.trainedAt', 'DESC')
            ->setParameter('user_id', $user->getId())
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function isConcept2ResultImported(User $user, int $concept2Id): bool
    {
        $count = (int) $this->createQueryBuilder('training')
            ->select('COUNT(training.id)')
            ->where('training.user = :user')
            ->andWhere('training.concept2Id = :concept2Id')
            ->setParameter('user', $user)
            ->setParameter('concept2Id', $concept2Id)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return $count > 0;
    }
}
