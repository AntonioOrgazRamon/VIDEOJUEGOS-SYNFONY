<?php

namespace App\Repository;

use App\Entity\GameRoom;
use App\Entity\User;
use App\Entity\Game;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameRoom>
 */
class GameRoomRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameRoom::class);
    }

    /**
     * Encuentra salas disponibles para unirse
     */
    public function findAvailableRooms(Game $game): array
    {
        return $this->createQueryBuilder('gr')
            ->where('gr.game = :game')
            ->andWhere('gr.status = :status')
            ->andWhere('gr.player2 IS NULL')
            ->setParameter('game', $game)
            ->setParameter('status', GameRoom::STATUS_WAITING)
            ->orderBy('gr.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra salas activas de un usuario
     */
    public function findActiveRoomsByUser(User $user): array
    {
        return $this->createQueryBuilder('gr')
            ->where('(gr.player1 = :user OR gr.player2 = :user)')
            ->andWhere('gr.status IN (:statuses)')
            ->setParameter('user', $user)
            ->setParameter('statuses', [GameRoom::STATUS_WAITING, GameRoom::STATUS_PLAYING])
            ->orderBy('gr.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra una sala por ID y usuario
     */
    public function findRoomForUser(int $roomId, User $user): ?GameRoom
    {
        return $this->createQueryBuilder('gr')
            ->where('gr.id = :roomId')
            ->andWhere('(gr.player1 = :user OR gr.player2 = :user)')
            ->setParameter('roomId', $roomId)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Encuentra salas finalizadas de un usuario
     */
    public function findFinishedRoomsByUser(User $user, int $limit = 10): array
    {
        return $this->createQueryBuilder('gr')
            ->where('(gr.player1 = :user OR gr.player2 = :user)')
            ->andWhere('gr.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', GameRoom::STATUS_FINISHED)
            ->orderBy('gr.finishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}


