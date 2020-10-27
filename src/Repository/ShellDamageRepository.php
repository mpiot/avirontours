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

use App\Entity\ShellDamage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;

/**
 * @method ShellDamage|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShellDamage|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShellDamage[]    findAll()
 * @method ShellDamage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShellDamageRepository extends ServiceEntityRepository
{
    /**
     * @var PaginatorInterface
     */
    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, ShellDamage::class);
        $this->paginator = $paginator;
    }

    public function findAllPaginated($page = 1): PaginationInterface
    {
        $query = $this->createQueryBuilder('shell_damage')
            ->addSelect('(
                CASE WHEN shell_damage.repairAt IS NOT NULL THEN 3
                     WHEN category.priority = 1 THEN 2
                     ELSE 1
                END) AS HIDDEN mainSort'
            )
            ->innerJoin('shell_damage.category', 'category')->addSelect('category')
            ->orderBy('mainSort', 'ASC')
            ->addOrderBy('shell_damage.createdAt', 'ASC')
            ->getQuery()
        ;

        return $this->paginator->paginate(
            $query,
            $page,
            ShellDamage::NUM_ITEMS
        );
    }
}
