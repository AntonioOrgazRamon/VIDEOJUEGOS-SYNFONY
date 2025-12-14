<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\User;
use App\Repository\UserActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AdminController extends AbstractController
{
    private const CACHE_KEY_GAMES_STATUS = 'games_status';
    private const CACHE_TTL_GAMES_STATUS = 5; // 5 segundos de caché

    #[Route('/admin/game/toggle-active/{gameId}', name: 'app_admin_toggle_game_active', methods: ['POST'])]
    public function toggleGameActive(int $gameId, EntityManagerInterface $entityManager, CacheInterface $cache): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        // Verificar que el usuario es admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'No tienes permisos de administrador'], 403);
        }

        $gameRepository = $entityManager->getRepository(Game::class);
        $game = $gameRepository->find($gameId);
        
        if (!$game) {
            return new JsonResponse(['success' => false, 'message' => 'Juego no encontrado'], 404);
        }

        // Cambiar el estado activo
        $game->setIsActive(!$game->isActive());
        $entityManager->persist($game);
        $entityManager->flush();

        // Invalidar caché de estado de juegos
        $cache->delete(self::CACHE_KEY_GAMES_STATUS);

        return new JsonResponse([
            'success' => true,
            'isActive' => $game->isActive(),
            'message' => $game->isActive() ? 'Juego activado' : 'Juego bloqueado'
        ]);
    }

    #[Route('/admin/games/status', name: 'app_admin_games_status', methods: ['GET'])]
    public function getGamesStatus(EntityManagerInterface $entityManager, CacheInterface $cache): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        // Usar caché para evitar consultas repetidas a la BD
        $status = $cache->get(self::CACHE_KEY_GAMES_STATUS, function (ItemInterface $item) use ($entityManager) {
            // TTL de 5 segundos
            $item->expiresAfter(self::CACHE_TTL_GAMES_STATUS);
            
            // Query optimizada: solo seleccionar campos necesarios
            $gameRepository = $entityManager->getRepository(Game::class);
            $games = $gameRepository->createQueryBuilder('g')
                ->select('g.id', 'g.name', 'g.isActive')
                ->orderBy('g.name', 'ASC')
                ->getQuery()
                ->getArrayResult();
            
            $status = [];
            foreach ($games as $game) {
                $status[$game['id']] = [
                    'id' => $game['id'],
                    'isActive' => $game['isActive'],
                    'name' => $game['name']
                ];
            }
            
            return $status;
        });

        // Respuesta con headers de caché HTTP
        $response = new JsonResponse([
            'success' => true,
            'games' => $status
        ]);

        // Headers de caché HTTP (5 segundos)
        $response->setPublic();
        $response->setMaxAge(self::CACHE_TTL_GAMES_STATUS);
        $response->headers->addCacheControlDirective('must-revalidate');

        return $response;
    }

    #[Route('/admin/users/active', name: 'app_admin_users_active', methods: ['GET'])]
    public function getActiveUsers(EntityManagerInterface $entityManager, UserActivityRepository $activityRepository, CacheInterface $cache): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        // Verificar que el usuario es admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'No tienes permisos de administrador'], 403);
        }

        try {
            $users = [];
            
            // Obtener usuarios activos (ya viene sin duplicados)
            $activities = $activityRepository->findActiveUsers();
            
            // Convertir actividades a array de usuarios
            foreach ($activities as $activity) {
                $userObj = $activity->getUser();
                
                if (!$userObj) {
                    continue;
                }
                
                $users[] = [
                    'id' => $userObj->getId(),
                    'username' => $userObj->getUsername(),
                    'email' => $userObj->getEmail(),
                    'profileImage' => $userObj->getProfileImage(),
                    'status' => $userObj->getStatus(),
                    'statusMessage' => $userObj->getStatusMessage(),
                    'currentGame' => $activity->getCurrentGame() ? [
                        'id' => $activity->getCurrentGame()->getId(),
                        'name' => $activity->getCurrentGame()->getName(),
                        'slug' => $activity->getCurrentGame()->getSlug()
                    ] : null,
                    'currentPage' => $activity->getCurrentPage() ?: 'Inicio',
                    'lastActivityAt' => $activity->getLastActivityAt() ? $activity->getLastActivityAt()->format('Y-m-d H:i:s') : null,
                    'isOnline' => $activity->isOnline(),
                    'ipAddress' => $activity->getIpAddress()
                ];
            }
            
            // Debug en desarrollo
            if ($_ENV['APP_ENV'] === 'dev') {
                error_log("🔍 getActiveUsers() - Total usuarios activos devueltos: " . count($users));
                if (count($users) === 0) {
                    error_log("  ⚠️ ADVERTENCIA: No se encontraron usuarios activos");
                }
                foreach ($users as $u) {
                    $gameName = $u['currentGame'] ? $u['currentGame']['name'] : 'ninguno';
                    $gameId = $u['currentGame'] ? $u['currentGame']['id'] : 'null';
                    error_log("  ✅ Usuario: {$u['username']} (ID: {$u['id']}) - Estado: " . ($u['isOnline'] ? 'ONLINE' : 'OFFLINE') . " - Juego: $gameName (ID: $gameId) - Página: {$u['currentPage']} - Última actividad: {$u['lastActivityAt']}");
                }
            }

            return new JsonResponse([
                'success' => true,
                'users' => $users,
                'count' => count($users)
            ]);
        } catch (\Exception $e) {
            error_log('Error en getActiveUsers: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
            return new JsonResponse([
                'success' => false,
                'message' => 'Error al obtener usuarios activos: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/admin/users/ban/{userId}', name: 'app_admin_ban_user', methods: ['POST'])]
    public function banUser(int $userId, EntityManagerInterface $entityManager, CacheInterface $cache): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        // Verificar que el usuario es admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'No tienes permisos de administrador'], 403);
        }

        // No puedes banearte a ti mismo
        if ($userId === $user->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'No puedes banearte a ti mismo'], 400);
        }

        $userRepository = $entityManager->getRepository(User::class);
        $targetUser = $userRepository->find($userId);

        if (!$targetUser) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Banear usuario (desactivar cuenta)
        $targetUser->setIsActive(false);
        $entityManager->persist($targetUser);
        $entityManager->flush();

        // Invalidar caché de usuarios activos
        $cache->delete('admin_active_users');

        return new JsonResponse([
            'success' => true,
            'message' => 'Usuario baneado correctamente',
            'user' => [
                'id' => $targetUser->getId(),
                'username' => $targetUser->getUsername(),
                'isActive' => $targetUser->isActive()
            ]
        ]);
    }

    #[Route('/admin/users/kick/{userId}', name: 'app_admin_kick_user', methods: ['POST'])]
    public function kickUser(int $userId, EntityManagerInterface $entityManager, TokenStorageInterface $tokenStorage, CacheInterface $cache): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        // Verificar que el usuario es admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'No tienes permisos de administrador'], 403);
        }

        // No puedes kickearte a ti mismo
        if ($userId === $user->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'No puedes kickearte a ti mismo'], 400);
        }

        $userRepository = $entityManager->getRepository(User::class);
        $targetUser = $userRepository->find($userId);

        if (!$targetUser) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Invalidar todas las sesiones del usuario (marcar como offline)
        $activityRepository = $entityManager->getRepository(\App\Entity\UserActivity::class);
        $activity = $activityRepository->findByUser($userId);
        
        if ($activity) {
            $activity->setIsOnline(false);
            $activity->setCurrentGame(null);
            $activity->setCurrentPage(null);
            $entityManager->persist($activity);
            $entityManager->flush();
        }

        // Intentar invalidar la sesión del usuario eliminando sesiones de la base de datos
        // Symfony guarda el user ID serializado en los datos de la sesión
        try {
            $connection = $entityManager->getConnection();
            
            // Buscar y eliminar sesiones que contengan el ID del usuario
            // El formato puede variar, así que buscamos diferentes patrones
            $userIdPattern = '%"id";i:' . $userId . ';%';
            $connection->executeStatement(
                "DELETE FROM sessions WHERE sess_data LIKE ?",
                [$userIdPattern]
            );
            
            // También intentar con el formato de serialización alternativo
            $userIdPattern2 = '%s:' . strlen((string)$userId) . ':"' . $userId . '"%';
            $connection->executeStatement(
                "DELETE FROM sessions WHERE sess_data LIKE ?",
                [$userIdPattern2]
            );
        } catch (\Exception $e) {
            // Si la tabla sessions no existe o hay un error, no es crítico
            // El usuario se desconectará en su próximo request cuando el listener detecte que está offline
            error_log('No se pudo invalidar sesión directamente: ' . $e->getMessage());
        }

        // Invalidar caché de usuarios activos
        $cache->delete('admin_active_users');

        return new JsonResponse([
            'success' => true,
            'message' => 'Usuario kickeado correctamente. Será redirigido al login.',
            'user' => [
                'id' => $targetUser->getId(),
                'username' => $targetUser->getUsername()
            ]
        ]);
    }

    #[Route('/admin/panel', name: 'app_admin_panel', methods: ['GET'])]
    public function adminPanel(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Verificar que el usuario es admin
        if (!in_array('ROLE_ADMIN', $user->getRoles())) {
            return $this->redirectToRoute('app_home');
        }

        return $this->render('admin/panel.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/admin/debug/activity', name: 'app_admin_debug_activity', methods: ['GET'])]
    public function debugActivity(EntityManagerInterface $entityManager, UserActivityRepository $activityRepository): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['error' => 'No autorizado'], 403);
        }

        // Contar registros en user_activity
        $totalActivities = $entityManager->createQuery('SELECT COUNT(ua.id) FROM App\Entity\UserActivity ua')->getSingleScalarResult();
        
        // Obtener todas las actividades recientes
        $allActivities = $activityRepository->createQueryBuilder('ua')
            ->orderBy('ua.lastActivityAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        
        $activitiesData = [];
        foreach ($allActivities as $activity) {
            $activitiesData[] = [
                'id' => $activity->getId(),
                'userId' => $activity->getUserId(),
                'username' => $activity->getUser() ? $activity->getUser()->getUsername() : 'N/A',
                'currentPage' => $activity->getCurrentPage(),
                'currentGame' => $activity->getCurrentGame() ? $activity->getCurrentGame()->getName() : null,
                'lastActivityAt' => $activity->getLastActivityAt() ? $activity->getLastActivityAt()->format('Y-m-d H:i:s') : null,
                'isOnline' => $activity->isOnline()
            ];
        }

        // Obtener usuarios con last_seen_at reciente
        $userRepository = $entityManager->getRepository(\App\Entity\User::class);
        $recentUsers = $userRepository->createQueryBuilder('u')
            ->where('u.lastSeenAt IS NOT NULL')
            ->orderBy('u.lastSeenAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();
        
        $usersData = [];
        foreach ($recentUsers as $u) {
            $usersData[] = [
                'id' => $u->getId(),
                'username' => $u->getUsername(),
                'lastSeenAt' => $u->getLastSeenAt() ? $u->getLastSeenAt()->format('Y-m-d H:i:s') : null
            ];
        }

        return new JsonResponse([
            'total_activities' => $totalActivities,
            'activities' => $activitiesData,
            'users_with_last_seen' => $usersData,
            'current_user_id' => $user->getId(),
            'current_username' => $user->getUsername()
        ]);
    }
}

