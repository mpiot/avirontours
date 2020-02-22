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

use App\Entity\Shell;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Shell|null find($id, $lockMode = null, $lockVersion = null)
 * @method Shell|null findOneBy(array $criteria, array $orderBy = null)
 * @method Shell[]    findAll()
 * @method Shell[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShellRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shell::class);
    }

    public function findAllNameOrdered()
    {
        $query = $this->createQueryBuilder('shell')
            ->orderBy('COLLATE(shell.name, fr_natural)', 'ASC')
            ->getQuery();

        return $query->getResult();
    }

    public function findTop10Sessions()
    {
        $today = new \DateTime();

        $query = $this->createQueryBuilder('shell')
            ->addSelect('COUNT(logbook_entries) as totalSessions')
            ->leftJoin('shell.logbookEntries', 'logbook_entries')
            ->where('logbook_entries.date BETWEEN :p30days AND :today')
            ->orderBy('totalSessions', 'DESC')
            ->groupBy('shell')
            ->setParameters([
                'today' => $today->format('Y-m-d'),
                'p30days' => $today->modify('-30 days')->format('Y-m-d'),
            ])
            ->getQuery()
            ->setMaxResults(10);

        return $query->getResult();
    }
}
