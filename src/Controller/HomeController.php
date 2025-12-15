<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\UserGameLike;
use App\Entity\UserScore;
use App\Repository\UserActivityRepository;
use App\Repository\UserGameLikeRepository;
use App\Repository\UserScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $user = $this->getUser();
        $gameRepository = $entityManager->getRepository(Game::class);
        $likeRepository = $entityManager->getRepository(UserGameLike::class);

        // Verificar si el usuario es admin
        $isAdmin = false;
        if ($user) {
            $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        }

        // Todos los usuarios ven todos los juegos (activos e inactivos)
        // Los usuarios normales verán los bloqueados con la clase game-disabled
        // Los admins pueden gestionarlos
        // Query optimizada con índice en is_active y name
        $games = $gameRepository->createQueryBuilder('g')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Crear una lista plana de juegos para mostrar (sin duplicados)
        // Cada juego aparece solo una vez, excepto en los casos especiales donde se dividen
        $gamesToDisplay = [];
        $gameIndex = 0;
        $cardIndex = 0;
        $totalGames = count($games);
        
        while ($gameIndex < $totalGames) {
            if ($cardIndex == 9) {
                // Dividir en 4 pequeños - usar 4 juegos diferentes
                for ($j = 0; $j < 4 && $gameIndex < $totalGames; $j++) {
                    $gamesToDisplay[] = [
                        'game' => $games[$gameIndex],
                        'type' => 'split',
                        'cardIndex' => $cardIndex + $j
                    ];
                    $gameIndex++;
                }
                $cardIndex += 4;
            } elseif ($cardIndex == 11) {
                // Dividir en 3 pequeños - usar 3 juegos diferentes
                for ($j = 0; $j < 3 && $gameIndex < $totalGames; $j++) {
                    $gamesToDisplay[] = [
                        'game' => $games[$gameIndex],
                        'type' => 'split',
                        'cardIndex' => $cardIndex + $j
                    ];
                    $gameIndex++;
                }
                $cardIndex += 3;
            } else {
                // Juego normal
                $gamesToDisplay[] = [
                    'game' => $games[$gameIndex],
                    'type' => 'normal',
                    'cardIndex' => $cardIndex
                ];
                $gameIndex++;
                $cardIndex++;
            }
        }

        // Obtener IDs de juegos favoritos del usuario
        $likedGameIds = [];
        if ($user) {
            $likedGameIds = $likeRepository->findLikedGameIdsByUser($user->getId());
        }

        // Obtener tema e idioma del usuario
        $themeMode = 'light';
        $language = 'es';
        if ($user) {
            $themeMode = $user->getThemeMode() ?? 'light';
            $language = $user->getLanguage() ?? 'es';
        }

        $response = $this->render('home/index.html.twig', [
            'user' => $user,
            'games' => $games,
            'gamesToDisplay' => $gamesToDisplay,
            'likedGameIds' => $likedGameIds,
            'isAdmin' => $isAdmin,
            'themeMode' => $themeMode,
            'language' => $language,
        ]);

        // Headers de caché HTTP para assets estáticos (30 minutos)
        // La página principal no se cachea porque es dinámica por usuario
        $response->setPrivate();
        $response->setMaxAge(0);
        $response->headers->addCacheControlDirective('must-revalidate');

        return $response;
    }

    #[Route('/api/user/status', name: 'app_user_status', methods: ['GET'])]
    public function getUserStatus(EntityManagerInterface $entityManager, UserActivityRepository $activityRepository): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse([
                'success' => false,
                'authenticated' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        // Refrescar el usuario desde la BD para obtener el estado actualizado
        $userRepository = $entityManager->getRepository(\App\Entity\User::class);
        $freshUser = $userRepository->find($user->getId());
        
        if (!$freshUser) {
            return new JsonResponse([
                'success' => false,
                'authenticated' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        // Verificar estado de actividad
        $activity = $activityRepository->findByUser($freshUser->getId());
        $isOnline = $activity ? $activity->isOnline() : false;

        return new JsonResponse([
            'success' => true,
            'authenticated' => true,
            'isActive' => $freshUser->isActive(),
            'isOnline' => $isOnline,
            'userId' => $freshUser->getId(),
            'username' => $freshUser->getUsername()
        ]);
    }

    #[Route('/game/toggle-like/{gameId}', name: 'app_game_toggle_like', methods: ['POST'])]
    public function toggleLike(int $gameId, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $gameRepository = $entityManager->getRepository(Game::class);
        $likeRepository = $entityManager->getRepository(UserGameLike::class);

        $game = $gameRepository->find($gameId);
        if (!$game) {
            return new JsonResponse(['success' => false, 'message' => 'Juego no encontrado'], 404);
        }

        // Verificar si ya existe el like
        $existingLike = $likeRepository->findByUserAndGame($user->getId(), $gameId);

        if ($existingLike) {
            // Eliminar like
            $entityManager->remove($existingLike);
            $entityManager->flush();
            return new JsonResponse(['success' => true, 'liked' => false]);
        } else {
            // Crear like
            $like = new UserGameLike();
            // Establecer relaciones primero
            $like->setUser($user);
            $like->setGame($game);
            // Asegurar que los IDs estén establecidos
            $like->setUserId($user->getId());
            $like->setGameId($game->getId());
            
            $entityManager->persist($like);
            $entityManager->flush();
            
            // Verificar que se guardó correctamente
            $savedLike = $likeRepository->findByUserAndGame($user->getId(), $gameId);
            if (!$savedLike) {
                return new JsonResponse(['success' => false, 'message' => 'Error al guardar el like'], 500);
            }
            
            return new JsonResponse(['success' => true, 'liked' => true]);
        }
    }

    #[Route('/game/play/{gameId}', name: 'app_game_play')]
    public function play(int $gameId, EntityManagerInterface $entityManager, UserScoreRepository $scoreRepository, CacheInterface $cache): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $gameRepository = $entityManager->getRepository(Game::class);
        $game = $gameRepository->find($gameId);

        if (!$game) {
            throw $this->createNotFoundException('Juego no encontrado');
        }

        // Obtener top 10 puntuaciones para este juego (con caché de 30 segundos)
        $cacheKey = 'top_scores_game_' . $gameId;
        $topScores = $cache->get($cacheKey, function (ItemInterface $item) use ($scoreRepository, $gameId) {
            $item->expiresAfter(30); // Caché de 30 segundos para rankings
            return $scoreRepository->findTopScoresByGame($gameId, 10);
        });

        // Verificar si el usuario está en el top 10
        $userInTop10 = false;
        foreach ($topScores as $score) {
            if ($score['score']->getUser()->getId() === $user->getId()) {
                $userInTop10 = true;
                break;
            }
        }

        // Si el usuario no está en el top 10, obtener su posición
        $userPosition = null;
        if (!$userInTop10) {
            $userBestScore = $scoreRepository->findUserBestScoreAndPosition($gameId, $user->getId());
            if ($userBestScore) {
                $userPosition = $userBestScore;
            }
        }

        // Obtener tema e idioma del usuario
        $themeMode = $user->getThemeMode() ?? 'light';
        $language = $user->getLanguage() ?? 'es';

        // Verificar si existe el template del juego en templates/games/{slug}/game.html.twig
        $gameSlug = $game->getSlug();
        $gameTemplate = 'games/' . $gameSlug . '/game.html.twig';
        $projectDir = $this->getParameter('kernel.project_dir');
        $gameTemplatePath = $projectDir . '/templates/' . $gameTemplate;
        $gameExists = file_exists($gameTemplatePath);

        return $this->render('game/play.html.twig', [
            'user' => $user,
            'game' => $game,
            'topScores' => $topScores,
            'userPosition' => $userPosition,
            'themeMode' => $themeMode,
            'language' => $language,
            'gameTemplate' => $gameExists ? $gameTemplate : null,
            'gameId' => $gameId,
        ]);
    }

    #[Route('/api/game/find-by-slug/{slug}', name: 'app_game_find_by_slug', methods: ['GET'])]
    public function findGameBySlug(string $slug, EntityManagerInterface $entityManager): JsonResponse
    {
        $gameRepository = $entityManager->getRepository(Game::class);
        $game = $gameRepository->findOneBy(['slug' => $slug]);

        if (!$game) {
            return new JsonResponse(['success' => false, 'message' => 'Juego no encontrado'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'game' => [
                'id' => $game->getId(),
                'name' => $game->getName(),
                'slug' => $game->getSlug()
            ]
        ]);
    }

    #[Route('/api/game/save-score', name: 'app_game_save_score', methods: ['POST'])]
    public function saveScore(Request $request, EntityManagerInterface $entityManager, CacheInterface $cache): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['game_id']) || !isset($data['score'])) {
            return new JsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
        }

        $gameId = (int)$data['game_id'];
        $score = (int)$data['score'];
        $duration = isset($data['duration']) ? (int)$data['duration'] : null;
        $level = isset($data['level']) ? (int)$data['level'] : null;

        $gameRepository = $entityManager->getRepository(Game::class);
        $game = $gameRepository->find($gameId);

        if (!$game) {
            return new JsonResponse(['success' => false, 'message' => 'Juego no encontrado'], 404);
        }

        // Crear nueva puntuación
        $userScore = new UserScore();
        $userScore->setUser($user);
        $userScore->setGame($game);
        $userScore->setScore($score);
        
        if ($duration !== null) {
            $userScore->setDuration($duration);
        }
        
        if ($level !== null) {
            $userScore->setLevel($level);
        }

        $entityManager->persist($userScore);
        $entityManager->flush();

        // Invalidar caché de rankings cuando se guarda una nueva puntuación
        $cache->delete('top_scores_game_' . $gameId);

        return new JsonResponse([
            'success' => true,
            'message' => 'Puntuación guardada correctamente',
            'score' => $score
        ]);
    }

    #[Route('/api/user/update-activity', name: 'app_user_update_activity', methods: ['POST'])]
    public function updateActivity(Request $request, EntityManagerInterface $entityManager, UserActivityRepository $activityRepository, CacheInterface $cache): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $gameId = isset($data['game_id']) ? (int)$data['game_id'] : null;
        $page = $data['page'] ?? null;
        
        // Si gameId es null pero la página contiene /game/play/, extraer el gameId de la página
        if ($gameId === null && $page !== null && preg_match('/\/game\/play\/(\d+)/', $page, $matches)) {
            $gameId = (int)$matches[1];
        }
        
        // Obtener IP y User Agent
        $ipAddress = $request->getClientIp();
        $userAgent = $request->headers->get('User-Agent');

        // Actualizar actividad
        $activity = $activityRepository->updateOrCreateActivity(
            $user->getId(),
            $gameId,
            $page,
            $ipAddress,
            $userAgent
        );

        // Invalidar caché de usuarios activos
        $cache->delete('admin_active_users');

        // Debug en desarrollo
        if ($_ENV['APP_ENV'] === 'dev') {
            $gameName = $activity->getCurrentGame() ? $activity->getCurrentGame()->getName() : 'ninguno';
            $savedGameId = $activity->getCurrentGame() ? $activity->getCurrentGame()->getId() : 'null';
            error_log("🔄 API updateActivity - Usuario: {$user->getUsername()} (ID: {$user->getId()}), Página: $page, Juego: $gameName (ID: $savedGameId), isOnline: " . ($activity->isOnline() ? 'true' : 'false'));
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Actividad actualizada',
            'game_id' => $gameId,
            'page' => $page
        ]);
    }

    #[Route('/api/user/mark-offline', name: 'app_user_mark_offline', methods: ['POST'])]
    public function markOffline(UserActivityRepository $activityRepository, CacheInterface $cache): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        // Marcar usuario como offline
        $activityRepository->markUserOffline($user->getId());

        // Invalidar caché de usuarios activos
        $cache->delete('admin_active_users');

        return new JsonResponse([
            'success' => true,
            'message' => 'Usuario marcado como offline'
        ]);
    }

    #[Route('/api/user/check-status', name: 'app_user_check_status', methods: ['GET'])]
    public function checkStatus(UserActivityRepository $activityRepository): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            // Si no hay usuario autenticado, no está kickeado (simplemente no está logueado)
            return new JsonResponse(['kicked' => false, 'authenticated' => false]);
        }

        // Verificar si el usuario está marcado como offline PERO está autenticado (fue kickeado)
        $activity = $activityRepository->findByUser($user->getId());
        $kicked = $activity && !$activity->isOnline();
        
        $response = new JsonResponse([
            'kicked' => $kicked,
            'authenticated' => true,
            'isOnline' => $activity ? $activity->isOnline() : false
        ]);
        
        // Añadir header especial si fue kickeado
        if ($kicked) {
            $response->headers->set('X-User-Kicked', 'true');
        }
        
        return $response;
    }
}
