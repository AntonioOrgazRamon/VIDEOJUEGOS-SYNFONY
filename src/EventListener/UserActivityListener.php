<?php

namespace App\EventListener;

use App\Repository\UserActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 10)]
class UserActivityListener
{
    public function __construct(
        private TokenStorageInterface $tokenStorage,
        private EntityManagerInterface $entityManager,
        private UserActivityRepository $activityRepository
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        // Solo procesar requests principales (no sub-requests)
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();
        
        // SIEMPRE loggear la ruta para debug (temporal)
        error_log("🔍 LISTENER - Ruta: $path");
        
        // Ignorar requests de assets, API y páginas públicas (para evitar loops)
        // PERO primero verificar si el usuario fue kickeado o baneado
        $shouldIgnore = str_starts_with($path, '/_') || 
            str_starts_with($path, '/api/user/update-activity') ||
            str_starts_with($path, '/api/user/status') ||
            str_starts_with($path, '/admin/users/active') ||
            str_starts_with($path, '/banned/appeal') ||
            str_starts_with($path, '/banned/status') ||
            str_starts_with($path, '/css/') ||
            str_starts_with($path, '/js/') ||
            str_starts_with($path, '/images/') ||
            str_starts_with($path, '/icons/') ||
            str_starts_with($path, '/build/');
        
        // IMPORTANTE: NO ignorar /game/play ni /admin/panel - deben actualizar actividad
        // Tampoco ignorar /login, /register, /password-reset, /kicked, /banned - necesitamos verificar estado

        // Obtener usuario autenticado
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser()) {
            if ($shouldIgnore) {
                error_log("  ⏭️ LISTENER - Ruta ignorada (sin usuario): $path");
                return;
            }
            error_log("  ⚠️ LISTENER - No hay token o usuario para ruta: $path");
            return;
        }

        $user = $token->getUser();
        
        // Verificar que es una instancia de User
        if (!is_object($user) || !method_exists($user, 'getId')) {
            if ($shouldIgnore) {
                error_log("  ⏭️ LISTENER - Ruta ignorada (usuario inválido): $path");
                return;
            }
            error_log("  ⚠️ LISTENER - Usuario no es objeto válido para ruta: $path");
            return;
        }
        
        $userId = $user->getId();
        
        // IMPORTANTE: Refrescar el usuario desde la BD para obtener el estado actualizado
        $userRepository = $this->entityManager->getRepository(\App\Entity\User::class);
        $freshUser = $userRepository->find($userId);
        
        if (!$freshUser) {
            error_log("  ⚠️ LISTENER - Usuario ID: $userId no encontrado en BD, invalidando sesión");
            $this->tokenStorage->setToken(null);
            $request->getSession()->invalidate();
            $response = new \Symfony\Component\HttpFoundation\RedirectResponse('/login?error=user_not_found');
            $event->setResponse($response);
            return;
        }
        
        // Verificar si el usuario está baneado (isActive = false)
        if (!$freshUser->isActive()) {
            // Si ya está en la página de baneo, permitir acceso
            if ($path === '/banned' || str_starts_with($path, '/banned/')) {
                // Permitir acceso a la página de baneo
                return;
            }
            
            error_log("  🚫 LISTENER - Usuario ID: $userId está BANEADO (isActive=false), redirigiendo a página de baneo");
            
            // Redirigir a la página de baneo (no invalidar sesión para que pueda enviar apelación)
            $response = new \Symfony\Component\HttpFoundation\RedirectResponse('/banned');
            $response->headers->set('X-User-Banned', 'true');
            $event->setResponse($response);
            
            return;
        }
        
        // Verificar si el usuario fue kickeado (offline pero autenticado)
        $activity = $this->activityRepository->findByUser($userId);
        if ($activity && !$activity->isOnline()) {
            // Si ya está en la página de kicked, permitir acceso
            if ($path === '/kicked') {
                return;
            }
            
            error_log("  🚪 LISTENER - Usuario ID: $userId fue KICKEADO (offline pero autenticado), redirigiendo a página de kicked desde: $path");
            
            // Redirigir a la página de kicked (incluso si está intentando acceder a /login)
            $response = new \Symfony\Component\HttpFoundation\RedirectResponse('/kicked');
            $response->headers->set('X-User-Kicked', 'true');
            $event->setResponse($response);
            
            return;
        }
        
        // Si debe ignorarse y no hay problemas de estado, ignorar ahora
        if ($shouldIgnore) {
            error_log("  ⏭️ LISTENER - Ruta ignorada: $path");
            return;
        }
        
        // Ignorar rutas públicas solo si no hay problemas de estado
        if (str_starts_with($path, '/login') || 
            str_starts_with($path, '/register') || 
            str_starts_with($path, '/password-reset')) {
            return;
        }
        
        // Actualizar actividad del usuario (marca como online automáticamente)
        try {
            $currentPage = $path;
            $gameId = null;
            
            // Extraer gameId de la URL si está en /game/play/{gameId}
            if (preg_match('/\/game\/play\/(\d+)/', $path, $matches)) {
                $gameId = (int)$matches[1];
                error_log("  🎮 LISTENER - Juego detectado en URL: ID = $gameId");
            }
            
            $ipAddress = $request->getClientIp();
            $userAgent = $request->headers->get('User-Agent');
            
            // Debug ANTES de actualizar
            $gameInfo = $gameId ? " (Juego ID: $gameId)" : " (NO en juego)";
            error_log("🔄 LISTENER - Usuario ID: $userId, Página: $currentPage$gameInfo");
            
            // Actualizar actividad (esto marca como online automáticamente y ya hace flush)
            $activity = $this->activityRepository->updateOrCreateActivity(
                $userId,
                $gameId,
                $currentPage,
                $ipAddress,
                $userAgent
            );
            
            // NO hacer persist/flush aquí porque updateOrCreateActivity ya lo hace
            
            // Debug DESPUÉS de actualizar
            $savedGameId = $activity->getCurrentGame() ? $activity->getCurrentGame()->getId() : 'NULL';
            $savedGameName = $activity->getCurrentGame() ? $activity->getCurrentGame()->getName() : 'ninguno';
            $isOnline = $activity->isOnline() ? 'ONLINE' : 'OFFLINE';
            error_log("✅ LISTENER - Actividad guardada - Usuario ID: $userId, Estado: $isOnline, Juego guardado: $savedGameName (ID: $savedGameId), Página: " . ($activity->getCurrentPage() ?: 'N/A'));
        } catch (\Exception $e) {
            // Log de errores
            error_log('❌ Error al actualizar actividad de usuario: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }
}

