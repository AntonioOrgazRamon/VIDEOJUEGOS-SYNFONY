<?php

namespace App\Repository;

use App\Entity\UserGameLike;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserGameLike>
 */
class UserGameLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGameLike::class);
    }

    public function findByUserAndGame(int $userId, int $gameId): ?UserGameLike
    {
        return $this->createQueryBuilder('l')
            ->where('l.userId = :userId')
            ->andWhere('l.gameId = :gameId')
            ->setParameter('userId', $userId)
            ->setParameter('gameId', $gameId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLikedGameIdsByUser(int $userId): array
    {
        $results = $this->createQueryBuilder('l')
            ->select('l.gameId')
            ->where('l.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();

        return array_column($results, 'gameId');
    }
}



