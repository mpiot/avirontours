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

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use function Symfony\Component\String\u;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private $paginator;

    public function __construct(ManagerRegistry $registry, PaginatorInterface $paginator)
    {
        parent::__construct($registry, User::class);
        $this->paginator = $paginator;
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findUserProfile(User $user)
    {
        $query = $this->createQueryBuilder('user')
            ->leftJoin('user.licenses', 'license')->addSelect('license')
            ->leftJoin('license.seasonCategory', 'seasonCategory')->addSelect('seasonCategory')
            ->leftJoin('seasonCategory.season', 'season')->addSelect('season')
            ->leftJoin('license.medicalCertificate', 'medicalCertificate')->addSelect('medicalCertificate')
            ->orderBy('season.name', 'DESC')
            ->where('user = :user')
            ->setParameter('user', $user)
            ->getQuery()
        ;

        return $query->getOneOrNullResult();
    }

    public function findPaginated($query = null, $page = 1): PaginationInterface
    {
        $qb = $this->createQueryBuilder('app_user')
            ->orderBy('app_user.firstName', 'ASC')
            ->addOrderBy('app_user.lastName', 'ASC')
        ;

        if ($query) {
            $qb
                ->orWhere('LOWER(app_user.firstName) LIKE :query')
                ->orWhere('LOWER(app_user.lastName) LIKE :query')
                ->orWhere('LOWER(app_user.email) LIKE :query')
                ->setParameter('query', '%'.u($query)->lower()->toString().'%')
            ;
        }

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            User::NUM_ITEMS
        );
    }

    public function findTop10Distances()
    {
        $today = new \DateTime();

        $query = $this->createQueryBuilder('app_user')
            ->addSelect('SUM(logbook_entries.coveredDistance) as totalDistance')
            ->leftJoin('app_user.logbookEntries', 'logbook_entries')
            ->where('logbook_entries.date BETWEEN :p30days AND :today')
            ->andWhere('logbook_entries.endAt IS NOT NULL')
            ->orderBy('totalDistance', 'DESC')
            ->groupBy('app_user.id')
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

        $query = $this->createQueryBuilder('app_user')
            ->addSelect('COUNT(logbook_entries) as totalSessions')
            ->leftJoin('app_user.logbookEntries', 'logbook_entries')
            ->where('logbook_entries.date BETWEEN :p30days AND :today')
            ->andWhere('logbook_entries.endAt IS NOT NULL')
            ->orderBy('totalSessions', 'DESC')
            ->groupBy('app_user.id')
            ->setParameters([
                'today' => $today->format('Y-m-d'),
                'p30days' => $today->modify('-30 days')->format('Y-m-d'),
            ])
            ->getQuery()
            ->setMaxResults(10);

        return $query->getResult();
    }
}
