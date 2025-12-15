<?php

namespace App\Repository;

use App\Entity\UserBanHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBanHistory>
 */
class UserBanHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBanHistory::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('h.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}


