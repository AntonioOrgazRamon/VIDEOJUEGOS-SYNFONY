<?php

namespace App\Repository;

use App\Entity\Friendship;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Friendship>
 */
class FriendshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Friendship::class);
    }

    /**
     * Encuentra una amistad entre dos usuarios
     */
    public function findFriendship(User $user1, User $user2): ?Friendship
    {
        return $this->createQueryBuilder('f')
            ->where('(f.user1 = :user1 AND f.user2 = :user2) OR (f.user1 = :user2 AND f.user2 = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Encuentra todas las amistades de un usuario (aceptadas)
     */
    public function findFriendshipsByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('(f.user1 = :user OR f.user2 = :user) AND f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_ACCEPTED)
            ->orderBy('f.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra solicitudes pendientes de un usuario
     */
    public function findPendingRequests(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.user2 = :user AND f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_PENDING)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Encuentra solicitudes enviadas pendientes de un usuario
     */
    public function findSentRequests(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.user1 = :user AND f.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Friendship::STATUS_PENDING)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Verifica si dos usuarios son amigos
     */
    public function areFriends(User $user1, User $user2): bool
    {
        $friendship = $this->findFriendship($user1, $user2);
        return $friendship !== null && $friendship->isAccepted();
    }
}


