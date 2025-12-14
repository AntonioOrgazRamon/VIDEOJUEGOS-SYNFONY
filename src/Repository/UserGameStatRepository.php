<?php

namespace App\Repository;

use App\Entity\UserGameStat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserGameStat>
 */
class UserGameStatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGameStat::class);
    }

    public function findByUserAndGame(int $userId, int $gameId): ?UserGameStat
    {
        return $this->createQueryBuilder('s')
            ->where('s.userId = :userId')
            ->andWhere('s.gameId = :gameId')
            ->setParameter('userId', $userId)
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}



