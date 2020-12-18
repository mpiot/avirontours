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

use App\Entity\LogbookEntry;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method LogbookEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method LogbookEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method LogbookEntry[]    findAll()
 * @method LogbookEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogbookEntryRepository extends ServiceEntityRepository
{
    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, LogbookEntry::class);
        $this->paginator = $paginator;
    }

    public function findAllPaginated($page = 1): PaginationInterface
    {
        $query = $this->createQueryBuilder('logbook_entry')
            ->addSelect('CASE WHEN logbook_entry.endAt IS NULL THEN 1 ELSE 0 END as HIDDEN end_is_null')
            ->leftJoin('logbook_entry.shell', 'shell')->addSelect('shell')
            ->leftJoin('logbook_entry.crewMembers', 'crew_members')->addSelect('crew_members')
            ->orderBy('end_is_null', 'DESC')
            ->addOrderBy('logbook_entry.date', 'DESC')
            ->addOrderBy('logbook_entry.startAt', 'DESC')
            ->getQuery()
        ;

        return $this->paginator->paginate(
            $query,
            $page,
            LogbookEntry::NUM_ITEMS
        );
    }

    public function findStatsByMonth(User $user, int $nbMonths = 12)
    {
        $today = new \DateTime();

        $query = $this->createQueryBuilder('logbook_entry')
            ->select('DATE_PART(\'month\', logbook_entry.date) AS month, SUM(logbook_entry.coveredDistance) as distance, COUNT(logbook_entry) as session')
            ->leftJoin('logbook_entry.crewMembers', 'crew_members')
            ->andWhere('crew_members = :user')
            ->andWhere('logbook_entry.date BETWEEN :lastDay AND :today')
            ->andWhere('logbook_entry.endAt IS NOT NULL')
            ->groupBy('month')
            ->setParameters([
                'user' => $user,
                'today' => $today->format('Y-m-d'),
                'lastDay' => $today->modify('-'.$nbMonths.' months')->modify('first day of this month')->format('Y-m-d'),
            ])
            ->getQuery();

        return $query->getResult();
    }
}
