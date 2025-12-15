<?php

namespace App\Repository;

use App\Entity\UserActivity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserActivity>
 */
class UserActivityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserActivity::class);
    }

    /**
     * Obtiene la actividad de un usuario (la más reciente)
     */
    public function findByUser(int $userId): ?UserActivity
    {
        return $this->createQueryBuilder('ua')
            ->where('ua.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ua.lastActivityAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Marca un usuario como offline
     */
    public function markUserOffline(int $userId): void
    {
        $activity = $this->findByUser($userId);
        if ($activity) {
            $activity->setIsOnline(false);
            $activity->setCurrentGame(null);
            $activity->setCurrentPage(null);
            
            $this->getEntityManager()->persist($activity);
            $this->getEntityManager()->flush();
            
            if ($_ENV['APP_ENV'] === 'dev') {
                error_log("🔴 Usuario ID: $userId marcado como offline en BD");
            }
        } else {
            // Si no existe actividad, crear una marcada como offline
            $userRepository = $this->getEntityManager()->getRepository(\App\Entity\User::class);
            $user = $userRepository->find($userId);
            if ($user) {
                $activity = new \App\Entity\UserActivity();
                $activity->setUser($user);
                $activity->setIsOnline(false);
                $activity->setCurrentGame(null);
                $activity->setCurrentPage(null);
                
                $this->getEntityManager()->persist($activity);
                $this->getEntityManager()->flush();
            }
        }
    }

    /**
     * Obtiene todos los usuarios activos (online en los últimos 2 minutos)
     * SOLO devuelve UN registro por usuario (el más reciente)
     */
    public function findActiveUsers(): array
    {
        $twoMinutesAgo = new \DateTime('-2 minutes');
        
        // Obtener todas las actividades recientes
        $allActivities = $this->createQueryBuilder('ua')
            ->select('ua')
            ->join('ua.user', 'u')
            ->leftJoin('ua.currentGame', 'g')
            ->where('ua.lastActivityAt >= :twoMinutesAgo')
            ->andWhere('ua.isOnline = :isOnline')
            ->andWhere('u.isActive = :userActive')
            ->setParameter('twoMinutesAgo', $twoMinutesAgo)
            ->setParameter('isOnline', true)
            ->setParameter('userActive', true)
            ->orderBy('ua.lastActivityAt', 'DESC')
            ->addOrderBy('ua.id', 'DESC')
            ->getQuery()
            ->getResult();
        
        // Agrupar por userId y quedarse solo con la más reciente de cada usuario
        $uniqueResults = [];
        $seenUserIds = [];
        
        foreach ($allActivities as $activity) {
            $user = $activity->getUser();
            if ($user) {
                $userId = $user->getId();
                // Solo tomar la primera (más reciente) actividad de cada usuario
                if (!isset($seenUserIds[$userId])) {
                    $seenUserIds[$userId] = true;
                    $uniqueResults[] = $activity;
                }
            }
        }
        
        // Debug en desarrollo
        if ($_ENV['APP_ENV'] === 'dev') {
            error_log("🔍 findActiveUsers() - Total actividades encontradas: " . count($allActivities) . ", Usuarios únicos: " . count($uniqueResults));
            error_log("   Criterios: lastActivityAt >= " . $twoMinutesAgo->format('Y-m-d H:i:s') . ", isOnline = true");
            foreach ($uniqueResults as $act) {
                $user = $act->getUser();
                $game = $act->getCurrentGame();
                $gameName = $game ? $game->getName() : 'ninguno';
                $lastActivity = $act->getLastActivityAt() ? $act->getLastActivityAt()->format('Y-m-d H:i:s') : 'N/A';
                $isOnline = $act->isOnline() ? 'ONLINE' : 'OFFLINE';
                error_log("  ✅ Usuario: " . ($user ? $user->getUsername() : 'N/A') . " (ID: " . ($user ? $user->getId() : 'N/A') . "), Estado: $isOnline, Juego: $gameName, Página: " . ($act->getCurrentPage() ?: 'N/A') . ", Última actividad: $lastActivity");
            }
            if (count($allActivities) > 0 && count($uniqueResults) === 0) {
                error_log("  ⚠️ ADVERTENCIA: Se encontraron actividades pero no se procesaron usuarios únicos");
            }
        }
        
        return $uniqueResults;
    }

    /**
     * Actualiza o crea la actividad de un usuario
     * IMPORTANTE: Siempre actualiza el registro existente, no crea múltiples
     */
    public function updateOrCreateActivity(int $userId, ?int $gameId = null, ?string $page = null, ?string $ipAddress = null, ?string $userAgent = null): UserActivity
    {
        // Buscar actividad existente del usuario
        $activity = $this->findByUser($userId);
        
        // Si no existe, crear una nueva
        if (!$activity) {
            $activity = new UserActivity();
            $userRepository = $this->getEntityManager()->getRepository(\App\Entity\User::class);
            $user = $userRepository->find($userId);
            if ($user) {
                $activity->setUser($user);
            } else {
                throw new \RuntimeException("Usuario con ID $userId no encontrado");
            }
        }
        
        // IMPORTANTE: Primero intentar extraer gameId de la página si no se proporcionó
        if ($gameId === null && $page !== null && str_contains($page, '/game/play/')) {
            if (preg_match('/\/game\/play\/(\d+)/', $page, $matches)) {
                $gameId = (int)$matches[1];
                if ($_ENV['APP_ENV'] === 'dev') {
                    error_log("  🎮 gameId extraído de página: $gameId");
                }
            }
        }
        
        // Actualizar actividad (marca como online y actualiza timestamp)
        // IMPORTANTE: Hacer esto DESPUÉS de extraer gameId para asegurar que se actualice correctamente
        $activity->updateActivity();
        
        // Asegurar explícitamente que isOnline sea true
        $activity->setIsOnline(true);
        
        // Actualizar juego
        if ($gameId !== null) {
            $gameRepository = $this->getEntityManager()->getRepository(\App\Entity\Game::class);
            $game = $gameRepository->find($gameId);
            if ($game) {
                $activity->setCurrentGame($game);
                if ($_ENV['APP_ENV'] === 'dev') {
                    error_log("  🎮 Juego asignado: {$game->getName()} (ID: $gameId)");
                }
            } else {
                // Si el juego no existe, limpiar
                $activity->setCurrentGame(null);
                if ($_ENV['APP_ENV'] === 'dev') {
                    error_log("  ⚠️ Juego ID $gameId no encontrado, limpiando juego");
                }
            }
        } elseif ($page !== null && !str_contains($page, '/game/play/')) {
            // Si no estamos en un juego y la página no es /game/play, limpiar el juego
            $activity->setCurrentGame(null);
            if ($_ENV['APP_ENV'] === 'dev') {
                error_log("  🏠 No en juego, limpiando currentGame");
            }
        } else {
            // Si estamos en /game/play pero gameId es null, mantener el juego actual (no limpiar)
            // IMPORTANTE: No limpiar el juego si ya está en uno
            if ($_ENV['APP_ENV'] === 'dev') {
                $currentGame = $activity->getCurrentGame();
                error_log("  🔄 En /game/play pero gameId es null, manteniendo juego actual: " . ($currentGame ? $currentGame->getName() : 'ninguno'));
            }
        }
        
        // Actualizar página
        if ($page !== null) {
            $activity->setCurrentPage($page);
        }
        
        // Actualizar IP y User Agent si se proporcionan
        if ($ipAddress !== null) {
            $activity->setIpAddress($ipAddress);
        }
        
        if ($userAgent !== null) {
            $activity->setUserAgent($userAgent);
        }
        
        // Actualizar también last_seen_at del usuario
        $userObj = $activity->getUser();
        if ($userObj) {
            $userObj->setLastSeenAt(new \DateTime());
            $this->getEntityManager()->persist($userObj);
        }
        
        // Asegurar que isOnline sea true antes de guardar
        $activity->setIsOnline(true);
        
        // Guardar cambios con Doctrine
        $this->getEntityManager()->persist($activity);
        if ($userObj) {
            $this->getEntityManager()->persist($userObj);
        }
        $this->getEntityManager()->flush();
        
        // IMPORTANTE: Forzar actualización directa en BD para asegurar que todo se guarde correctamente
        // Esto evita problemas con Doctrine que pueden no persistir correctamente
        $connection = $this->getEntityManager()->getConnection();
        
        // Construir la query de actualización
        $updateFields = ['is_online = 1', 'last_activity_at = NOW()'];
        $params = [];
        
        if ($gameId !== null) {
            $updateFields[] = 'current_game_id = ?';
            $params[] = $gameId;
        } else {
            // Si no hay gameId pero estamos en /game/play/, mantener el juego actual
            if ($page !== null && str_contains($page, '/game/play/')) {
                // No actualizar current_game_id, mantener el actual
            } else {
                // Si no estamos en un juego, limpiar current_game_id
                $updateFields[] = 'current_game_id = NULL';
            }
        }
        
        if ($page !== null) {
            $updateFields[] = 'current_page = ?';
            $params[] = $page;
        }
        
        $params[] = $userId; // Para el WHERE
        
        $sql = "UPDATE user_activity SET " . implode(', ', $updateFields) . " WHERE user_id = ?";
        $connection->executeStatement($sql, $params);
        
        // Debug en desarrollo
        if ($_ENV['APP_ENV'] === 'dev') {
            $gameName = $activity->getCurrentGame() ? $activity->getCurrentGame()->getName() : 'ninguno';
            $savedGameId = $activity->getCurrentGame() ? $activity->getCurrentGame()->getId() : 'null';
            $isOnline = $activity->isOnline() ? 'ONLINE' : 'OFFLINE';
            $lastActivity = $activity->getLastActivityAt() ? $activity->getLastActivityAt()->format('Y-m-d H:i:s') : 'N/A';
            error_log("📝 Actividad actualizada - Usuario: {$userObj->getUsername()} (ID: $userId), Estado: $isOnline, Página: $page, Juego: $gameName (ID: $savedGameId), Última actividad: $lastActivity");
        }
        
        return $activity;
    }
}
