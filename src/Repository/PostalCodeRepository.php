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

use App\Entity\PostalCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostalCode>
 *
 * @method PostalCode|null find($id, $lockMode = null, $lockVersion = null)
 * @method PostalCode|null findOneBy(array $criteria, array $orderBy = null)
 * @method PostalCode[]    findAll()
 * @method PostalCode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PostalCodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostalCode::class);
    }

    public function save(PostalCode $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PostalCode $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function exists(int|string $postalCode, string $city): bool
    {
        $result = $this->createQueryBuilder('postal_code')
            ->where('postal_code.postalCode = :postal_code AND postal_code.city = :city')
            ->setParameters([
                'postal_code' => $postalCode,
                'city' => $city,
            ])
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return null !== $result;
    }
}
