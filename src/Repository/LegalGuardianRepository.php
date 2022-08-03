<?php

namespace App\Repository;

use App\Entity\LegalGuardian;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LegalGuardian>
 *
 * @method LegalGuardian|null find($id, $lockMode = null, $lockVersion = null)
 * @method LegalGuardian|null findOneBy(array $criteria, array $orderBy = null)
 * @method LegalGuardian[]    findAll()
 * @method LegalGuardian[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 * @psalm-method list<LegalGuardian> findAll()
 * @psalm-method list<LegalGuardian> findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LegalGuardianRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LegalGuardian::class);
    }
}
