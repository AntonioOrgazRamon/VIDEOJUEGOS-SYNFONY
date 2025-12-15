<?php

namespace App\Controller;

use App\Entity\Friendship;
use App\Entity\User;
use App\Repository\FriendshipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class FriendshipController extends AbstractController
{
    #[Route('/friends', name: 'app_friends')]
    public function friends(): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        return $this->render('friends/index.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/api/friends/send-request/{userId}', name: 'app_friends_send_request', methods: ['POST'])]
    public function sendRequest(
        int $userId,
        EntityManagerInterface $entityManager,
        FriendshipRepository $friendshipRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        if ($currentUser->getId() === $userId) {
            return new JsonResponse(['success' => false, 'message' => 'No puedes enviarte una solicitud a ti mismo'], 400);
        }

        $userRepository = $entityManager->getRepository(User::class);
        $targetUser = $userRepository->find($userId);

        if (!$targetUser) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Verificar si ya existe una amistad
        $existingFriendship = $friendshipRepository->findFriendship($currentUser, $targetUser);
        
        if ($existingFriendship) {
            if ($existingFriendship->isAccepted()) {
                return new JsonResponse(['success' => false, 'message' => 'Ya sois amigos'], 400);
            }
            if ($existingFriendship->isPending()) {
                return new JsonResponse(['success' => false, 'message' => 'Ya hay una solicitud pendiente'], 400);
            }
        }

        // Crear nueva solicitud
        $friendship = new Friendship();
        $friendship->setUser1($currentUser);
        $friendship->setUser2($targetUser);
        $friendship->setStatus(Friendship::STATUS_PENDING);
        $friendship->setRequestedBy($currentUser->getId());

        $entityManager->persist($friendship);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Solicitud de amistad enviada',
            'friendship' => [
                'id' => $friendship->getId(),
                'status' => $friendship->getStatus()
            ]
        ]);
    }

    #[Route('/api/friends/accept/{friendshipId}', name: 'app_friends_accept', methods: ['POST'])]
    public function acceptRequest(
        int $friendshipId,
        EntityManagerInterface $entityManager,
        FriendshipRepository $friendshipRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $friendship = $friendshipRepository->find($friendshipId);

        if (!$friendship) {
            return new JsonResponse(['success' => false, 'message' => 'Solicitud no encontrada'], 404);
        }

        // Verificar que el usuario actual es el destinatario
        if ($friendship->getUser2()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'No tienes permiso para aceptar esta solicitud'], 403);
        }

        if (!$friendship->isPending()) {
            return new JsonResponse(['success' => false, 'message' => 'Esta solicitud ya fue procesada'], 400);
        }

        $friendship->setStatus(Friendship::STATUS_ACCEPTED);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Solicitud aceptada',
            'friendship' => [
                'id' => $friendship->getId(),
                'status' => $friendship->getStatus(),
                'user' => [
                    'id' => $friendship->getUser1()->getId(),
                    'username' => $friendship->getUser1()->getUsername(),
                    'email' => $friendship->getUser1()->getEmail()
                ]
            ]
        ]);
    }

    #[Route('/api/friends/reject/{friendshipId}', name: 'app_friends_reject', methods: ['POST'])]
    public function rejectRequest(
        int $friendshipId,
        EntityManagerInterface $entityManager,
        FriendshipRepository $friendshipRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $friendship = $friendshipRepository->find($friendshipId);

        if (!$friendship) {
            return new JsonResponse(['success' => false, 'message' => 'Solicitud no encontrada'], 404);
        }

        // Verificar que el usuario actual es el destinatario o el remitente
        if ($friendship->getUser2()->getId() !== $currentUser->getId() && 
            $friendship->getUser1()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'No tienes permiso para rechazar esta solicitud'], 403);
        }

        $entityManager->remove($friendship);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Solicitud rechazada'
        ]);
    }

    #[Route('/api/friends/list', name: 'app_friends_list', methods: ['GET'])]
    public function getFriends(FriendshipRepository $friendshipRepository): JsonResponse
    {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $friendships = $friendshipRepository->findFriendshipsByUser($currentUser);
        
        $friends = [];
        foreach ($friendships as $friendship) {
            $friend = $friendship->getOtherUser($currentUser);
            if ($friend) {
                $friends[] = [
                    'id' => $friend->getId(),
                    'username' => $friend->getUsername(),
                    'email' => $friend->getEmail(),
                    'status' => $friend->getStatus(),
                    'profileImage' => $friend->getProfileImage(),
                    'friendshipId' => $friendship->getId()
                ];
            }
        }
        
        // Ordenar por ID para mantener consistencia en el orden
        usort($friends, function($a, $b) {
            return $a['id'] <=> $b['id'];
        });

        return new JsonResponse([
            'success' => true,
            'friends' => $friends
        ]);
    }

    #[Route('/api/friends/pending', name: 'app_friends_pending', methods: ['GET'])]
    public function getPendingRequests(FriendshipRepository $friendshipRepository): JsonResponse
    {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $pending = $friendshipRepository->findPendingRequests($currentUser);
        $sent = $friendshipRepository->findSentRequests($currentUser);
        
        $pendingRequests = [];
        foreach ($pending as $friendship) {
            $requester = $friendship->getUser1();
            $pendingRequests[] = [
                'id' => $friendship->getId(),
                'user' => [
                    'id' => $requester->getId(),
                    'username' => $requester->getUsername(),
                    'email' => $requester->getEmail(),
                    'profileImage' => $requester->getProfileImage()
                ],
                'createdAt' => $friendship->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        $sentRequests = [];
        foreach ($sent as $friendship) {
            $target = $friendship->getUser2();
            $sentRequests[] = [
                'id' => $friendship->getId(),
                'user' => [
                    'id' => $target->getId(),
                    'username' => $target->getUsername(),
                    'email' => $target->getEmail(),
                    'profileImage' => $target->getProfileImage()
                ],
                'createdAt' => $friendship->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }

        return new JsonResponse([
            'success' => true,
            'pending' => $pendingRequests,
            'sent' => $sentRequests
        ]);
    }

    #[Route('/api/friends/remove/{friendshipId}', name: 'app_friends_remove', methods: ['POST'])]
    public function removeFriend(
        int $friendshipId,
        EntityManagerInterface $entityManager,
        FriendshipRepository $friendshipRepository
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $friendship = $friendshipRepository->find($friendshipId);

        if (!$friendship) {
            return new JsonResponse(['success' => false, 'message' => 'Amistad no encontrada'], 404);
        }

        // Verificar que el usuario actual es parte de la amistad
        if ($friendship->getUser1()->getId() !== $currentUser->getId() && 
            $friendship->getUser2()->getId() !== $currentUser->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'No tienes permiso para eliminar esta amistad'], 403);
        }

        $entityManager->remove($friendship);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Amigo eliminado'
        ]);
    }

    #[Route('/api/friends/search', name: 'app_friends_search', methods: ['GET'])]
    public function searchUsers(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $query = $request->query->get('q', '');
        if (strlen($query) < 2) {
            return new JsonResponse(['success' => true, 'users' => []]);
        }

        $userRepository = $entityManager->getRepository(User::class);
        $users = $userRepository->createQueryBuilder('u')
            ->where('u.username LIKE :query OR u.email LIKE :query')
            ->andWhere('u.id != :currentUserId')
            ->andWhere('u.isActive = :active')
            ->setParameter('query', '%' . $query . '%')
            ->setParameter('currentUserId', $currentUser->getId())
            ->setParameter('active', true)
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'profileImage' => $user->getProfileImage(),
                'status' => $user->getStatus()
            ];
        }

        return new JsonResponse([
            'success' => true,
            'users' => $results
        ]);
    }
}
