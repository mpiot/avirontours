<?php

namespace App\Repository;

use App\Entity\SeasonUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method SeasonUser|null find($id, $lockMode = null, $lockVersion = null)
 * @method SeasonUser|null findOneBy(array $criteria, array $orderBy = null)
 * @method SeasonUser[]    findAll()
 * @method SeasonUser[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeasonUserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SeasonUser::class);
    }

    // /**
    //  * @return SeasonUser[] Returns an array of SeasonUser objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SeasonUser
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
