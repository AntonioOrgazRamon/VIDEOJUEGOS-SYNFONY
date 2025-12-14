<?php

namespace App\Controller;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/game/toggle-active/{gameId}', name: 'app_admin_toggle_game_active', methods: ['POST'])]
    public function toggleGameActive(int $gameId, EntityManagerInterface $entityManager): JsonResponse
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

        return new JsonResponse([
            'success' => true,
            'isActive' => $game->isActive(),
            'message' => $game->isActive() ? 'Juego activado' : 'Juego bloqueado'
        ]);
    }

    #[Route('/admin/games/status', name: 'app_admin_games_status', methods: ['GET'])]
    public function getGamesStatus(EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        // Cualquier usuario autenticado puede ver el estado de los juegos
        // (necesario para que los usuarios normales vean en tiempo real cuando se bloquean)
        $gameRepository = $entityManager->getRepository(Game::class);
        $games = $gameRepository->findAll();
        
        $status = [];
        foreach ($games as $game) {
            $status[$game->getId()] = [
                'id' => $game->getId(),
                'isActive' => $game->isActive(),
                'name' => $game->getName()
            ];
        }

        return new JsonResponse([
            'success' => true,
            'games' => $status
        ]);
    }
}

