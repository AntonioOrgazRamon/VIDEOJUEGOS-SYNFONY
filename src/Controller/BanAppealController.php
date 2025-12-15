<?php

namespace App\Controller;

use App\Entity\BanAppeal;
use App\Entity\User;
use App\Repository\BanAppealRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BanAppealController extends AbstractController
{
    #[Route('/banned', name: 'app_banned')]
    public function banned(EntityManagerInterface $entityManager, BanAppealRepository $appealRepository): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $userRepository = $entityManager->getRepository(User::class);
        $freshUser = $userRepository->find($user->getId());
        
        if (!$freshUser || $freshUser->isActive()) {
            return $this->redirectToRoute('app_home');
        }

        $allAppeals = $appealRepository->findAppealsByUser($user->getId());
        
        $latestAppeal = null;
        
        foreach ($allAppeals as $appeal) {
            if ($appeal->getStatus() === 'pending') {
                $latestAppeal = $appeal;
                break;
            }
        }
        
        if (!$latestAppeal && count($allAppeals) > 0) {
            $mostRecentAppeal = $allAppeals[0];
            $now = new \DateTime();
            $appealDate = $mostRecentAppeal->getCreatedAt();
            $hoursSinceAppeal = ($now->getTimestamp() - $appealDate->getTimestamp()) / 3600;
            
            if ($hoursSinceAppeal < 24 && $mostRecentAppeal->getStatus() === 'rejected') {
                $latestAppeal = $mostRecentAppeal;
            }
        }
        
        $banHistoryRepository = $entityManager->getRepository(\App\Entity\UserBanHistory::class);
        $banHistory = $banHistoryRepository->findByUser($user->getId());

        return $this->render('ban/banned.html.twig', [
            'user' => $freshUser,
            'latestAppeal' => $latestAppeal,
            'allAppeals' => $allAppeals,
            'banHistory' => $banHistory
        ]);
    }

    #[Route('/banned/status', name: 'app_banned_status', methods: ['GET'])]
    public function getBannedStatus(EntityManagerInterface $entityManager, BanAppealRepository $appealRepository): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'No autenticado'], 401);
        }

        $userRepository = $entityManager->getRepository(User::class);
        $freshUser = $userRepository->find($user->getId());
        
        if (!$freshUser) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $allAppeals = $appealRepository->findAppealsByUser($user->getId());
        
        $latestAppeal = null;
        
        foreach ($allAppeals as $appeal) {
            if ($appeal->getStatus() === 'pending') {
                $latestAppeal = $appeal;
                break;
            }
        }
        
        if (!$latestAppeal && count($allAppeals) > 0 && !$freshUser->isActive()) {
            $mostRecentAppeal = $allAppeals[0];
            $now = new \DateTime();
            $appealDate = $mostRecentAppeal->getCreatedAt();
            $hoursSinceAppeal = ($now->getTimestamp() - $appealDate->getTimestamp()) / 3600;
            
            if ($hoursSinceAppeal < 24 && $mostRecentAppeal->getStatus() === 'rejected') {
                $latestAppeal = $mostRecentAppeal;
            }
        }

        return new JsonResponse([
            'success' => true,
            'isActive' => $freshUser->isActive(),
            'banMessage' => $freshUser->getBanMessage(),
            'latestAppeal' => $latestAppeal ? [
                'id' => $latestAppeal->getId(),
                'status' => $latestAppeal->getStatus(),
                'message' => $latestAppeal->getMessage(),
                'adminResponse' => $latestAppeal->getAdminResponse(),
                'createdAt' => $latestAppeal->getCreatedAt() ? $latestAppeal->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'reviewedAt' => $latestAppeal->getReviewedAt() ? $latestAppeal->getReviewedAt()->format('Y-m-d H:i:s') : null
            ] : null
        ]);
    }

    #[Route('/banned/appeal', name: 'app_banned_appeal', methods: ['POST'])]
    public function createAppeal(Request $request, EntityManagerInterface $entityManager, BanAppealRepository $appealRepository): JsonResponse
    {
        error_log('🚀 createAppeal - INICIO');
        
        try {
            $user = $this->getUser();
            error_log('👤 Usuario obtenido: ' . ($user ? $user->getId() : 'null'));
            
            if (!$user) {
                error_log('❌ No hay usuario autenticado');
                return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
            }

            $userRepository = $entityManager->getRepository(User::class);
            $freshUser = $userRepository->find($user->getId());
            
            if (!$freshUser) {
                error_log('❌ Usuario no encontrado en BD');
                return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
            }
            
            if ($freshUser->isActive()) {
                error_log('⚠️ Usuario no está baneado');
                return new JsonResponse(['success' => false, 'message' => 'No estás baneado'], 400);
            }

            $content = $request->getContent();
            error_log('📥 Contenido recibido: ' . substr($content, 0, 100));
            
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log('❌ Error JSON: ' . json_last_error_msg());
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Error al procesar los datos: ' . json_last_error_msg()
                ], 400);
            }
            
            $message = trim($data['message'] ?? '');
            error_log('📝 Mensaje recibido: ' . substr($message, 0, 50));

            if (empty($message) || strlen($message) < 10) {
                error_log('❌ Mensaje muy corto');
                return new JsonResponse([
                    'success' => false,
                    'message' => 'El mensaje debe tener al menos 10 caracteres'
                ], 400);
            }

            if (strlen($message) > 2000) {
                error_log('❌ Mensaje muy largo');
                return new JsonResponse([
                    'success' => false,
                    'message' => 'El mensaje no puede exceder 2000 caracteres'
                ], 400);
            }

            // Verificar si ya hay una apelación pendiente
            $pendingAppeal = $appealRepository->createQueryBuilder('ba')
                ->join('ba.user', 'u')
                ->where('u.id = :userId')
                ->andWhere('ba.status = :status')
                ->setParameter('userId', $user->getId())
                ->setParameter('status', 'pending')
                ->getQuery()
                ->getOneOrNullResult();

            if ($pendingAppeal) {
                error_log('⚠️ Ya hay una apelación pendiente');
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Ya tienes una apelación pendiente. Espera a que sea revisada.'
                ], 400);
            }

            // Crear nueva apelación
            error_log('✅ Creando nueva apelación...');
            $appeal = new BanAppeal();
            $appeal->setUser($freshUser);
            $appeal->setMessage($message);
            $appeal->setStatus('pending');

            error_log('💾 Persistiendo apelación...');
            error_log('💾 Usuario asignado: ' . ($appeal->getUser() ? $appeal->getUser()->getId() : 'null'));
            error_log('💾 Mensaje: ' . substr($appeal->getMessage(), 0, 50));
            error_log('💾 Status: ' . $appeal->getStatus());
            
            $entityManager->persist($appeal);
            
            try {
                $entityManager->flush();
                error_log('✅ Flush exitoso');
            } catch (\Exception $flushError) {
                error_log('❌ Error en flush: ' . $flushError->getMessage());
                error_log('❌ Stack: ' . $flushError->getTraceAsString());
                throw $flushError;
            }
            
            $appealId = $appeal->getId();
            error_log('✅ Apelación guardada con ID: ' . ($appealId ?? 'null'));
            
            if (!$appealId) {
                error_log('❌ ERROR CRÍTICO: La apelación no tiene ID después del flush');
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Error al guardar la apelación. Por favor, intenta de nuevo.'
                ], 500);
            }

            return new JsonResponse([
                'success' => true,
                'message' => 'Apelación enviada correctamente. Los administradores la revisarán pronto.',
                'appeal' => [
                    'id' => $appeal->getId(),
                    'message' => $appeal->getMessage(),
                    'status' => $appeal->getStatus(),
                    'createdAt' => $appeal->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Throwable $e) {
            error_log('❌ ERROR en createAppeal: ' . $e->getMessage());
            error_log('❌ Archivo: ' . $e->getFile() . ' Línea: ' . $e->getLine());
            error_log('❌ Stack trace: ' . $e->getTraceAsString());
            return new JsonResponse([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/admin/ban-appeals', name: 'app_admin_ban_appeals', methods: ['GET'])]
    public function getBanAppeals(BanAppealRepository $appealRepository): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $appeals = $appealRepository->findPendingAppeals();
        
        $appealsData = [];
        foreach ($appeals as $appeal) {
            $userObj = $appeal->getUser();
            $appealsData[] = [
                'id' => $appeal->getId(),
                'userId' => $userObj ? $userObj->getId() : null,
                'username' => $userObj ? $userObj->getUsername() : 'N/A',
                'email' => $userObj ? $userObj->getEmail() : 'N/A',
                'message' => $appeal->getMessage(),
                'status' => $appeal->getStatus(),
                'createdAt' => $appeal->getCreatedAt() ? $appeal->getCreatedAt()->format('Y-m-d H:i:s') : null,
                'adminResponse' => $appeal->getAdminResponse(),
                'reviewedAt' => $appeal->getReviewedAt() ? $appeal->getReviewedAt()->format('Y-m-d H:i:s') : null,
                'banMessage' => $userObj ? $userObj->getBanMessage() : null
            ];
        }

        return new JsonResponse([
            'success' => true,
            'appeals' => $appealsData,
            'count' => count($appealsData)
        ]);
    }

    #[Route('/admin/ban-appeals/{appealId}/review', name: 'app_admin_review_appeal', methods: ['POST'])]
    public function reviewAppeal(int $appealId, Request $request, EntityManagerInterface $entityManager, BanAppealRepository $appealRepository): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $appeal = $appealRepository->find($appealId);
        
        if (!$appeal) {
            return new JsonResponse(['success' => false, 'message' => 'Apelación no encontrada'], 404);
        }

        if ($appeal->getStatus() !== 'pending') {
            return new JsonResponse(['success' => false, 'message' => 'Esta apelación ya fue procesada'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $action = $data['action'] ?? '';
        $adminResponse = trim($data['response'] ?? '');

        if (!in_array($action, ['approve', 'reject'])) {
            return new JsonResponse(['success' => false, 'message' => 'Acción inválida'], 400);
        }

        $appeal->setStatus($action === 'approve' ? 'approved' : 'rejected');
        $appeal->setReviewedBy($user->getId());
        $appeal->setReviewedAt(new \DateTime());
        
        if (!empty($adminResponse)) {
            $appeal->setAdminResponse($adminResponse);
        }

        if ($action === 'approve') {
            $targetUser = $appeal->getUser();
            if ($targetUser) {
                $targetUser->setIsActive(true);
                $targetUser->setBanMessage(null);
                $entityManager->persist($targetUser);
                
                $unbanHistory = new \App\Entity\UserBanHistory();
                $unbanHistory->setUser($targetUser);
                $unbanHistory->setActionType('unban');
                $unbanHistory->setMessage('Usuario desbaneado mediante apelación aprobada');
                $unbanHistory->setPerformedBy($user->getId());
                $entityManager->persist($unbanHistory);
            }
        }

        $entityManager->persist($appeal);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => $action === 'approve' 
                ? 'Apelación aprobada y usuario desbaneado' 
                : 'Apelación rechazada',
            'appeal' => [
                'id' => $appeal->getId(),
                'status' => $appeal->getStatus(),
                'adminResponse' => $appeal->getAdminResponse()
            ]
        ]);
    }

    #[Route('/admin/user/{userId}/ban-history', name: 'app_admin_user_ban_history', methods: ['GET'])]
    public function getUserBanHistory(int $userId, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user || !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['success' => false, 'message' => 'No autorizado'], 403);
        }

        $banHistoryRepository = $entityManager->getRepository(\App\Entity\UserBanHistory::class);
        $banHistory = $banHistoryRepository->findByUser($userId);
        
        $historyData = [];
        foreach ($banHistory as $entry) {
            $historyData[] = [
                'id' => $entry->getId(),
                'actionType' => $entry->getActionType(),
                'message' => $entry->getMessage(),
                'performedBy' => $entry->getPerformedBy(),
                'createdAt' => $entry->getCreatedAt() ? $entry->getCreatedAt()->format('Y-m-d H:i:s') : null
            ];
        }

        return new JsonResponse([
            'success' => true,
            'history' => $historyData,
            'count' => count($historyData)
        ]);
    }
}
