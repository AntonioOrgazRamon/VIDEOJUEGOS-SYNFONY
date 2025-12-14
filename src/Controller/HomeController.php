<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\UserGameLike;
use App\Repository\UserGameLikeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
        $games = $gameRepository->findBy([], ['name' => 'ASC']);

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

        return $this->render('home/index.html.twig', [
            'user' => $user,
            'games' => $games,
            'gamesToDisplay' => $gamesToDisplay,
            'likedGameIds' => $likedGameIds,
            'isAdmin' => $isAdmin,
            'themeMode' => $themeMode,
            'language' => $language,
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
}
