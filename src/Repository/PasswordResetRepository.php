<?php

namespace App\Repository;

use App\Entity\PasswordReset;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordReset>
 */
class PasswordResetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordReset::class);
    }

    /**
     * Encuentra todos los resets activos (no usados y no expirados) de un usuario
     */
    public function findActiveResetsByUser(int $userId): array
    {
        return $this->createQueryBuilder('pr')
            ->where('pr.user = :userId')
            ->andWhere('pr.usedAt IS NULL')
            ->andWhere('pr.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }
}

