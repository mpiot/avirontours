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

use App\Entity\Group;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

use function Symfony\Component\String\u;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, UserLoaderInterface
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        \assert($user instanceof User);
        $user->setPassword($newHashedPassword);

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function loadUserByIdentifier(string $identifier): ?User
    {
        $em = $this->getEntityManager();

        return $em
            ->createQuery(/* @lang DQL */
                'SELECT user
                FROM App\Entity\User user
                WHERE user.username = :username'
            )
            ->setParameter('username', u($identifier)->ascii()->trim()->lower()->replace(' ', '-')->toString())
            ->getOneOrNullResult()
        ;
    }

    public function findUserProfile(User $user): mixed
    {
        $query = $this->createQueryBuilder('user')
            ->leftJoin('user.licenses', 'license')->addSelect('license')
            ->leftJoin('license.seasonCategory', 'seasonCategory')->addSelect('seasonCategory')
            ->leftJoin('seasonCategory.season', 'season')->addSelect('season')
            ->leftJoin('license.medicalCertificate', 'medicalCertificate')->addSelect('medicalCertificate')
            ->orderBy('season.name', 'DESC')
            ->where('user.id = :user')
            ->setParameter('user', $user->getId())
            ->getQuery()
        ;

        return $query->getOneOrNullResult();
    }

    public function findPaginated($query = null, int $page = 1): PaginationInterface
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
                ->setParameter('query', '%'.u($query)->trim()->lower()->toString().'%')
            ;
        }

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            User::NUM_ITEMS
        );
    }

    public function findUsersTrainings(?\DateTime $from = null, ?\DateTime $to = null, ?Group $group = null, $query = null, int $page = 1): PaginationInterface
    {
        $qb = $this->createQueryBuilder('app_user')
            ->innerJoin('app_user.trainings', 'trainings', Join::WITH, 'trainings.trainedAt BETWEEN :from AND :to')
            ->addSelect('trainings')
            ->orderBy('app_user.firstName', 'ASC')
            ->addOrderBy('app_user.lastName', 'ASC')
            ->setParameter('from', $from)
            ->setParameter('to', $to)
        ;

        if (null !== $group) {
            $qb
                ->innerJoin('app_user.groups', 'user_group')
                ->andWhere('user_group.id = :group')
                ->setParameter('group', $group->getId())
            ;
        }

        if ($query) {
            $qb
                ->andWhere('LOWER(app_user.firstName) LIKE :query OR LOWER(app_user.lastName) LIKE :query OR LOWER(app_user.email) LIKE :query')
                ->setParameter('query', '%'.u($query)->trim()->lower()->toString().'%')
            ;
        }

        return $this->paginator->paginate(
            $qb->getQuery(),
            $page,
            User::NUM_ITEMS
        );
    }

    public function findTop10Distances(): array
    {
        $today = new \DateTime();

        $query = $this->createQueryBuilder('app_user')
            ->addSelect('SUM(logbook_entries.coveredDistance) as totalDistance')
            ->leftJoin('app_user.logbookEntries', 'logbook_entries')
            ->where('logbook_entries.date BETWEEN :p30days AND :today')
            ->andWhere('logbook_entries.endAt IS NOT NULL')
            ->orderBy('totalDistance', 'DESC')
            ->groupBy('app_user.id')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('p30days', $today->modify('-30 days')->format('Y-m-d'))
            ->getQuery()
            ->setMaxResults(10)
        ;

        return $query->getResult();
    }

    public function findTop10Sessions(): array
    {
        $today = new \DateTime();

        $query = $this->createQueryBuilder('app_user')
            ->addSelect('COUNT(logbook_entries) as totalSessions')
            ->leftJoin('app_user.logbookEntries', 'logbook_entries')
            ->where('logbook_entries.date BETWEEN :p30days AND :today')
            ->andWhere('logbook_entries.endAt IS NOT NULL')
            ->orderBy('totalSessions', 'DESC')
            ->groupBy('app_user.id')
            ->setParameter('today', $today->format('Y-m-d'))
            ->setParameter('p30days', $today->modify('-30 days')->format('Y-m-d'))
            ->getQuery()
            ->setMaxResults(10)
        ;

        return $query->getResult();
    }

    public function findForUniqueness(array $args): array
    {
        if (null === $args['firstName'] || null === $args['lastName']) {
            return [];
        }

        $query = $this->createQueryBuilder('user')
            ->where('user.username = :username')
            ->setParameter('username', User::buildUsername($args['firstName'], $args['lastName']))
            ->getQuery()
        ;

        return $query->getResult();
    }

    public function findOnWaterUsers(?array $users = null): array
    {
        $qb = $this->createQueryBuilder('user')
            ->innerJoin('user.logbookEntries', 'logbook_entries', 'WITH', 'logbook_entries.endAt is NULL')
        ;

        if (null !== $users && [] !== $users) {
            $qb
                ->where('user IN (:users)')
                ->setParameter('users', $users)
            ;
        }

        return $qb->getQuery()->getResult();
    }
}
