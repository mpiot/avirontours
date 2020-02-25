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

use App\Entity\Member;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method Member|null find($id, $lockMode = null, $lockVersion = null)
 * @method Member|null findOneBy(array $criteria, array $orderBy = null)
 * @method Member[]    findAll()
 * @method Member[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MemberRepository extends ServiceEntityRepository
{
    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, Member::class);
        $this->paginator = $paginator;
    }

    public function findAllPaginated($page = 1): PaginationInterface
    {
        $query = $this->createQueryBuilder('app_member')
            ->orderBy('app_member.firstName', 'ASC')
            ->addOrderBy('app_member.lastName', 'ASC')
            ->getQuery()
        ;

        return $this->paginator->paginate(
            $query,
            $page,
            Member::NUM_ITEMS
        );
    }

    public function findTop10Distances()
    {
        $today = new \DateTime();

        $query = $this->createQueryBuilder('app_member')
            ->addSelect('SUM(logbook_entries.coveredDistance) as totalDistance')
            ->leftJoin('app_member.logbookEntries', 'logbook_entries')
            ->where('logbook_entries.date BETWEEN :p30days AND :today')
            ->orderBy('totalDistance', 'DESC')
            ->groupBy('app_member')
            ->setParameters([
                'today' => $today->format('Y-m-d'),
                'p30days' => $today->modify('-30 days')->format('Y-m-d'),
            ])
            ->getQuery()
            ->setMaxResults(10);

        return $query->getResult();
    }

    public function findTop10Sessions()
    {
        $today = new \DateTime();

        $query = $this->createQueryBuilder('app_member')
            ->addSelect('COUNT(logbook_entries) as totalSessions')
            ->leftJoin('app_member.logbookEntries', 'logbook_entries')
            ->where('logbook_entries.date BETWEEN :p30days AND :today')
            ->orderBy('totalSessions', 'DESC')
            ->groupBy('app_member')
            ->setParameters([
                'today' => $today->format('Y-m-d'),
                'p30days' => $today->modify('-30 days')->format('Y-m-d'),
            ])
            ->getQuery()
            ->setMaxResults(10);

        return $query->getResult();
    }
}
