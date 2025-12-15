<?php

namespace App\Repository;

use App\Entity\BanAppeal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BanAppeal>
 */
class BanAppealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BanAppeal::class);
    }

    public function findPendingAppeals(): array
    {
        return $this->createQueryBuilder('ba')
            ->where('ba.status = :status')
            ->setParameter('status', 'pending')
            ->orderBy('ba.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAppealsByUser(int $userId): array
    {
        return $this->createQueryBuilder('ba')
            ->join('ba.user', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ba.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestAppealByUser(int $userId): ?BanAppeal
    {
        return $this->createQueryBuilder('ba')
            ->join('ba.user', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ba.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
