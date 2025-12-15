<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\GameInvitation;
use App\Entity\GameRoom;
use App\Entity\User;
use App\Repository\FriendshipRepository;
use App\Repository\GameInvitationRepository;
use App\Repository\GameRoomRepository;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class GameRoomController extends AbstractController
{
    #[Route('/game-room/{roomId}', name: 'app_game_room_play', methods: ['GET'])]
    public function playRoom(
        int $roomId,
        GameRoomRepository $roomRepository
    ): Response {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return $this->redirectToRoute('app_login');
        }

        $room = $roomRepository->findRoomForUser($roomId, $currentUser);
        if (!$room) {
            throw $this->createNotFoundException('Sala no encontrada');
        }

        return $this->render('game-room/play.html.twig', [
            'room' => $room,
            'user' => $currentUser,
            'game' => $room->getGame()
        ]);
    }
    #[Route('/api/game-room/create/{gameId}', name: 'app_game_room_create', methods: ['POST'])]
    public function createRoom(
        int $gameId,
        EntityManagerInterface $entityManager,
        GameRepository $gameRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $game = $gameRepository->find($gameId);
        if (!$game) {
            return new JsonResponse(['success' => false, 'message' => 'Juego no encontrado'], 404);
        }

        // Verificar si el usuario ya tiene una sala activa
        $roomRepository = $entityManager->getRepository(GameRoom::class);
        $activeRooms = $roomRepository->findActiveRoomsByUser($currentUser);
        if (!empty($activeRooms)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Ya tienes una sala activa',
                'roomId' => $activeRooms[0]->getId()
            ], 400);
        }

        $room = new GameRoom();
        $room->setGame($game);
        $room->setPlayer1($currentUser);
        $room->setStatus(GameRoom::STATUS_WAITING);

        $entityManager->persist($room);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Sala creada',
            'room' => [
                'id' => $room->getId(),
                'gameId' => $game->getId(),
                'gameName' => $game->getName(),
                'status' => $room->getStatus(),
                'player1' => [
                    'id' => $currentUser->getId(),
                    'username' => $currentUser->getUsername()
                ]
            ]
        ]);
    }

    #[Route('/api/game-room/join/{roomId}', name: 'app_game_room_join', methods: ['POST'])]
    public function joinRoom(
        int $roomId,
        EntityManagerInterface $entityManager,
        GameRoomRepository $roomRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $room = $roomRepository->find($roomId);
        if (!$room) {
            return new JsonResponse(['success' => false, 'message' => 'Sala no encontrada'], 404);
        }

        if (!$room->isWaiting()) {
            return new JsonResponse(['success' => false, 'message' => 'Esta sala ya no está disponible'], 400);
        }

        if ($room->getPlayer1()->getId() === $currentUser->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Ya eres el creador de esta sala'], 400);
        }

        if ($room->getPlayer2() !== null) {
            return new JsonResponse(['success' => false, 'message' => 'Esta sala ya está llena'], 400);
        }

        $room->setPlayer2($currentUser);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Te has unido a la sala',
            'room' => $this->serializeRoom($room)
        ]);
    }

    #[Route('/api/game-room/{roomId}', name: 'app_game_room_get', methods: ['GET'])]
    public function getRoom(
        int $roomId,
        GameRoomRepository $roomRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $room = $roomRepository->findRoomForUser($roomId, $currentUser);
        if (!$room) {
            return new JsonResponse(['success' => false, 'message' => 'Sala no encontrada'], 404);
        }

        return new JsonResponse([
            'success' => true,
            'room' => $this->serializeRoom($room)
        ]);
    }

    #[Route('/api/game-room/{roomId}/move', name: 'app_game_room_move', methods: ['POST'])]
    public function makeMove(
        int $roomId,
        Request $request,
        EntityManagerInterface $entityManager,
        GameRoomRepository $roomRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $room = $roomRepository->findRoomForUser($roomId, $currentUser);
        if (!$room) {
            return new JsonResponse(['success' => false, 'message' => 'Sala no encontrada'], 404);
        }

        if (!$room->isPlaying()) {
            return new JsonResponse(['success' => false, 'message' => 'El juego no ha comenzado'], 400);
        }

        if (!$room->isPlayerTurn($currentUser)) {
            return new JsonResponse(['success' => false, 'message' => 'No es tu turno'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $column = $data['column'] ?? null;

        if ($column === null || !is_numeric($column) || $column < 0 || $column > 6) {
            return new JsonResponse(['success' => false, 'message' => 'Columna inválida'], 400);
        }

        $gameState = $room->getGameState() ?? $this->initializeGameState();
        $playerNumber = $room->getPlayer1()->getId() === $currentUser->getId() ? 1 : 2;

        // Hacer el movimiento
        $result = $this->makeMoveInGame($gameState, (int)$column, $playerNumber);
        
        if (!$result['success']) {
            return new JsonResponse(['success' => false, 'message' => $result['message']], 400);
        }

        $room->setGameState($gameState);

        // Verificar si hay ganador
        $winner = $this->checkWinner($gameState);
        if ($winner) {
            $room->setStatus(GameRoom::STATUS_FINISHED);
            $room->setWinnerId($winner === 1 ? $room->getPlayer1()->getId() : $room->getPlayer2()->getId());
        } else {
            // Cambiar turno
            $room->switchTurn();
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Movimiento realizado',
            'gameState' => $gameState,
            'room' => $this->serializeRoom($room),
            'winner' => $winner
        ]);
    }

    #[Route('/api/game-room/invite/{friendId}/{gameId}', name: 'app_game_room_invite', methods: ['POST'])]
    public function inviteFriend(
        int $friendId,
        int $gameId,
        EntityManagerInterface $entityManager,
        GameRepository $gameRepository,
        FriendshipRepository $friendshipRepository,
        GameInvitationRepository $invitationRepository,
        GameRoomRepository $roomRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        // Verificar que son amigos
        $userRepository = $entityManager->getRepository(User::class);
        $friend = $userRepository->find($friendId);
        if (!$friend) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        if (!$friendshipRepository->areFriends($currentUser, $friend)) {
            return new JsonResponse(['success' => false, 'message' => 'Debes ser amigo de este usuario'], 403);
        }

        $game = $gameRepository->find($gameId);
        if (!$game) {
            return new JsonResponse(['success' => false, 'message' => 'Juego no encontrado'], 404);
        }

        // Verificar si ya hay una invitación pendiente
        $existingInvitation = $invitationRepository->createQueryBuilder('gi')
            ->where('gi.inviter = :inviter')
            ->andWhere('gi.invitedUser = :invited')
            ->andWhere('gi.game = :game')
            ->andWhere('gi.status = :status')
            ->setParameter('inviter', $currentUser)
            ->setParameter('invited', $friend)
            ->setParameter('game', $game)
            ->setParameter('status', GameInvitation::STATUS_PENDING)
            ->getQuery()
            ->getOneOrNullResult();

        if ($existingInvitation) {
            // Obtener la sala asociada
            $existingRoom = $roomRepository->find($existingInvitation->getRoomId());
            return new JsonResponse([
                'success' => false,
                'message' => 'Ya has enviado una invitación a este usuario',
                'invitationId' => $existingInvitation->getId(),
                'roomId' => $existingInvitation->getRoomId(),
                'room' => $existingRoom ? $this->serializeRoom($existingRoom) : null
            ], 400);
        }

        // Crear sala primero
        $activeRooms = $roomRepository->findActiveRoomsByUser($currentUser);
        
        // Filtrar solo salas que están esperando (waiting) y que no tienen player2
        $waitingRooms = array_filter($activeRooms, function($r) {
            return $r->getStatus() === GameRoom::STATUS_WAITING && $r->getPlayer2() === null;
        });
        
        if (!empty($waitingRooms)) {
            $room = reset($waitingRooms); // Tomar la primera sala en espera
            if ($_ENV['APP_ENV'] === 'dev') {
                error_log("🔍 Usando sala existente ID: {$room->getId()}");
            }
        } else {
            $room = new GameRoom();
            $room->setGame($game);
            $room->setPlayer1($currentUser);
            $room->setStatus(GameRoom::STATUS_WAITING);
            $entityManager->persist($room);
            $entityManager->flush();
            
            if ($_ENV['APP_ENV'] === 'dev') {
                error_log("✅ Sala creada ID: {$room->getId()}");
            }
        }

        // Crear invitación
        $invitation = new GameInvitation();
        $invitation->setInviter($currentUser);
        $invitation->setInvitedUser($friend);
        $invitation->setGame($game);
        $invitation->setRoomId($room->getId());
        $invitation->setStatus(GameInvitation::STATUS_PENDING);

        $entityManager->persist($invitation);
        $entityManager->flush();

        if ($_ENV['APP_ENV'] === 'dev') {
            error_log("✅ Invitación creada ID: {$invitation->getId()}, Sala ID: {$room->getId()}, Para usuario ID: {$friend->getId()}");
        }

        // Serializar la sala completa para la respuesta
        $roomData = $this->serializeRoom($room);

        return new JsonResponse([
            'success' => true,
            'message' => 'Invitación enviada',
            'invitation' => [
                'id' => $invitation->getId(),
                'roomId' => $room->getId()
            ],
            'room' => $roomData
        ]);
    }

    #[Route('/api/game-room/invitations', name: 'app_game_room_invitations', methods: ['GET'])]
    public function getInvitations(GameInvitationRepository $invitationRepository): JsonResponse
    {
        error_log("🚀 getInvitations - INICIO");
        
        try {
            $currentUser = $this->getUser();
            error_log("👤 Usuario obtenido: " . ($currentUser ? $currentUser->getId() : 'null'));
            
            if (!$currentUser) {
                error_log("❌ No hay usuario autenticado");
                return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
            }

            error_log("🔍 getInvitations - Usuario ID: {$currentUser->getId()}");

            $invitations = $invitationRepository->findPendingInvitations($currentUser);
            
            error_log("✅ Invitaciones encontradas en repositorio: " . count($invitations));
            
            $result = [];
            foreach ($invitations as $invitation) {
                try {
                    $inviter = $invitation->getInviter();
                    $game = $invitation->getGame();
                    
                    if (!$inviter || !$game) {
                        error_log("⚠️ Invitación ID {$invitation->getId()} tiene relaciones nulas");
                        continue;
                    }
                    
                    $result[] = [
                        'id' => $invitation->getId(),
                        'inviter' => [
                            'id' => $inviter->getId(),
                            'username' => $inviter->getUsername(),
                            'profileImage' => $inviter->getProfileImage()
                        ],
                        'game' => [
                            'id' => $game->getId(),
                            'name' => $game->getName()
                        ],
                        'roomId' => $invitation->getRoomId(),
                        'createdAt' => $invitation->getCreatedAt() ? $invitation->getCreatedAt()->format('Y-m-d H:i:s') : null
                    ];
                } catch (\Exception $e) {
                    error_log("❌ Error procesando invitación ID {$invitation->getId()}: " . $e->getMessage());
                    error_log("❌ Stack trace: " . $e->getTraceAsString());
                    continue;
                }
            }

            error_log("✅ Invitaciones procesadas: " . count($result));

            return new JsonResponse([
                'success' => true,
                'invitations' => $result,
                'count' => count($result)
            ]);
        } catch (\Throwable $e) {
            error_log("❌ Error fatal en getInvitations: " . $e->getMessage());
            error_log("❌ Archivo: " . $e->getFile() . " Línea: " . $e->getLine());
            error_log("❌ Stack trace: " . $e->getTraceAsString());
            return new JsonResponse([
                'success' => false,
                'message' => 'Error al cargar invitaciones: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    #[Route('/api/game-room/invitation/{invitationId}/accept', name: 'app_game_room_accept_invitation', methods: ['POST'])]
    public function acceptInvitation(
        int $invitationId,
        EntityManagerInterface $entityManager,
        GameInvitationRepository $invitationRepository,
        GameRoomRepository $roomRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $invitation = $invitationRepository->findInvitationForUser($invitationId, $currentUser);
        if (!$invitation) {
            return new JsonResponse(['success' => false, 'message' => 'Invitación no encontrada'], 404);
        }

        if (!$invitation->isPending()) {
            return new JsonResponse(['success' => false, 'message' => 'Esta invitación ya fue procesada'], 400);
        }

        if ($invitation->isExpired()) {
            $invitation->setStatus(GameInvitation::STATUS_EXPIRED);
            $entityManager->flush();
            return new JsonResponse(['success' => false, 'message' => 'Esta invitación ha expirado'], 400);
        }

        // Unirse a la sala
        $room = $roomRepository->find($invitation->getRoomId());
        if (!$room || !$room->isWaiting()) {
            return new JsonResponse(['success' => false, 'message' => 'La sala ya no está disponible'], 400);
        }

        $room->setPlayer2($currentUser);
        $invitation->setStatus(GameInvitation::STATUS_ACCEPTED);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Invitación aceptada',
            'roomId' => $room->getId()
        ]);
    }

    #[Route('/api/game-room/invitation/{invitationId}/reject', name: 'app_game_room_reject_invitation', methods: ['POST'])]
    public function rejectInvitation(
        int $invitationId,
        EntityManagerInterface $entityManager,
        GameInvitationRepository $invitationRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $invitation = $invitationRepository->findInvitationForUser($invitationId, $currentUser);
        if (!$invitation) {
            return new JsonResponse(['success' => false, 'message' => 'Invitación no encontrada'], 404);
        }

        $invitation->setStatus(GameInvitation::STATUS_REJECTED);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Invitación rechazada'
        ]);
    }

    #[Route('/api/game-room/available/{gameId}', name: 'app_game_room_available', methods: ['GET'])]
    public function getAvailableRooms(
        int $gameId,
        GameRepository $gameRepository,
        GameRoomRepository $roomRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $game = $gameRepository->find($gameId);
        if (!$game) {
            return new JsonResponse(['success' => false, 'message' => 'Juego no encontrado'], 404);
        }

        $rooms = $roomRepository->findAvailableRooms($game);
        
        $serializedRooms = [];
        foreach ($rooms as $room) {
            $serializedRooms[] = [
                'id' => $room->getId(),
                'player1' => [
                    'id' => $room->getPlayer1()->getId(),
                    'username' => $room->getPlayer1()->getUsername()
                ],
                'createdAt' => $room->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse([
            'success' => true,
            'rooms' => $serializedRooms
        ]);
    }

    private function serializeRoom(GameRoom $room): array
    {
        $player1 = $room->getPlayer1();
        $player2 = $room->getPlayer2();
        
        return [
            'id' => $room->getId(),
            'gameId' => $room->getGame()->getId(),
            'gameName' => $room->getGame()->getName(),
            'status' => $room->getStatus(),
            'player1' => [
                'id' => $player1->getId(),
                'username' => $player1->getUsername(),
                'profileImage' => $player1->getProfileImage()
            ],
            'player2' => $player2 ? [
                'id' => $player2->getId(),
                'username' => $player2->getUsername(),
                'profileImage' => $player2->getProfileImage()
            ] : null,
            'currentTurn' => $room->getCurrentTurn(),
            'gameState' => $room->getGameState(),
            'winnerId' => $room->getWinnerId(),
            'createdAt' => $room->getCreatedAt()->format('Y-m-d H:i:s')
        ];
    }

    private function initializeGameState(): array
    {
        return [
            'board' => array_fill(0, 6, array_fill(0, 7, 0)),
            'currentPlayer' => 1,
            'moves' => 0
        ];
    }

    private function makeMoveInGame(array &$gameState, int $column, int $player): array
    {
        $board = &$gameState['board'];
        
        // Buscar la fila más baja disponible en la columna
        for ($row = 5; $row >= 0; $row--) {
            if ($board[$row][$column] === 0) {
                $board[$row][$column] = $player;
                $gameState['moves']++;
                return ['success' => true, 'row' => $row];
            }
        }
        
        return ['success' => false, 'message' => 'Columna llena'];
    }

    private function checkWinner(array $gameState): ?int
    {
        $board = $gameState['board'];
        
        // Verificar horizontal, vertical y diagonales
        for ($row = 0; $row < 6; $row++) {
            for ($col = 0; $col < 7; $col++) {
                if ($board[$row][$col] !== 0) {
                    $player = $board[$row][$col];
                    
                    // Horizontal
                    if ($col <= 3 && 
                        $board[$row][$col] === $player &&
                        $board[$row][$col+1] === $player &&
                        $board[$row][$col+2] === $player &&
                        $board[$row][$col+3] === $player) {
                        return $player;
                    }
                    
                    // Vertical
                    if ($row <= 2 &&
                        $board[$row][$col] === $player &&
                        $board[$row+1][$col] === $player &&
                        $board[$row+2][$col] === $player &&
                        $board[$row+3][$col] === $player) {
                        return $player;
                    }
                    
                    // Diagonal \
                    if ($row <= 2 && $col <= 3 &&
                        $board[$row][$col] === $player &&
                        $board[$row+1][$col+1] === $player &&
                        $board[$row+2][$col+2] === $player &&
                        $board[$row+3][$col+3] === $player) {
                        return $player;
                    }
                    
                    // Diagonal /
                    if ($row <= 2 && $col >= 3 &&
                        $board[$row][$col] === $player &&
                        $board[$row+1][$col-1] === $player &&
                        $board[$row+2][$col-2] === $player &&
                        $board[$row+3][$col-3] === $player) {
                        return $player;
                    }
                }
            }
        }
        
        // Verificar empate
        if ($gameState['moves'] >= 42) {
            return 0; // Empate
        }
        
        return null;
    }
}

