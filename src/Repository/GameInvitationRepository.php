<?php

namespace App\Repository;

use App\Entity\GameInvitation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GameInvitation>
 */
class GameInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameInvitation::class);
    }

    /**
     * Encuentra invitaciones pendientes para un usuario
     */
    public function findPendingInvitations(User $user): array
    {
        $now = new \DateTime();
        
        error_log("🔍 Buscando invitaciones para usuario ID: {$user->getId()}");
        
        try {
            error_log("🔍 Ejecutando query para usuario ID: {$user->getId()}");
            error_log("🔍 Fecha actual: " . $now->format('Y-m-d H:i:s'));
            
            $invitations = $this->createQueryBuilder('gi')
                ->leftJoin('gi.inviter', 'inviter')
                ->leftJoin('gi.invitedUser', 'invited')
                ->leftJoin('gi.game', 'game')
                ->addSelect('inviter')
                ->addSelect('invited')
                ->addSelect('game')
                ->where('gi.invitedUser = :user')
                ->andWhere('gi.status = :status')
                ->andWhere('(gi.expiresAt IS NULL OR gi.expiresAt > :now)')
                ->setParameter('user', $user)
                ->setParameter('status', GameInvitation::STATUS_PENDING)
                ->setParameter('now', $now)
                ->orderBy('gi.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
            
            error_log("✅ Query ejecutada correctamente, invitaciones encontradas: " . count($invitations));
        } catch (\Throwable $e) {
            error_log("❌ Error en findPendingInvitations: " . $e->getMessage());
            error_log("❌ Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
            error_log("❌ Stack trace: " . $e->getTraceAsString());
            return [];
        }
        
        return $invitations;
    }

    /**
     * Encuentra una invitación específica
     */
    public function findInvitationForUser(int $invitationId, User $user): ?GameInvitation
    {
        return $this->createQueryBuilder('gi')
            ->where('gi.id = :id')
            ->andWhere('gi.invitedUser = :user')
            ->setParameter('id', $invitationId)
            ->setParameter('user', $user)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

