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
        
        // Ignorar requests de assets y API de actividad (para evitar loops)
        if (str_starts_with($path, '/_') || 
            str_starts_with($path, '/api/user/update-activity') ||
            str_starts_with($path, '/admin/users/active') ||
            str_starts_with($path, '/css/') ||
            str_starts_with($path, '/js/') ||
            str_starts_with($path, '/images/') ||
            str_starts_with($path, '/icons/') ||
            str_starts_with($path, '/build/')) {
            error_log("  ⏭️ LISTENER - Ruta ignorada: $path");
            return;
        }
        
        // IMPORTANTE: NO ignorar /game/play ni /admin/panel - deben actualizar actividad

        // Obtener usuario autenticado
        $token = $this->tokenStorage->getToken();
        if (!$token || !$token->getUser()) {
            error_log("  ⚠️ LISTENER - No hay token o usuario para ruta: $path");
            return;
        }

        $user = $token->getUser();
        
        // Verificar que es una instancia de User
        if (!is_object($user) || !method_exists($user, 'getId')) {
            error_log("  ⚠️ LISTENER - Usuario no es objeto válido para ruta: $path");
            return;
        }
        
        $userId = $user->getId();
        
        // Verificar si el usuario está marcado como offline PERO está autenticado (fue kickeado)
        // IMPORTANTE: NO desconectar a admins (pueden estar offline por otras razones)
        $isAdmin = is_object($user) && method_exists($user, 'getRoles') && in_array('ROLE_ADMIN', $user->getRoles());
        
        if (!$isAdmin) {
            // Solo verificar para usuarios normales, no para admins
            $activity = $this->activityRepository->findByUser($userId);
            if ($activity && !$activity->isOnline()) {
                // Usuario normal está autenticado pero marcado como offline = fue kickeado
                error_log("  🚪 LISTENER - Usuario ID: $userId está autenticado pero marcado como offline (kickeado), invalidando sesión");
                
                // Invalidar sesión
                $this->tokenStorage->setToken(null);
                $request->getSession()->invalidate();
                
                // Crear respuesta de redirección al login
                $response = new \Symfony\Component\HttpFoundation\RedirectResponse('/login?kicked=1');
                $response->headers->set('X-User-Kicked', 'true');
                $event->setResponse($response);
                
                return;
            }
        }

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
            
            // Actualizar actividad
            $activity = $this->activityRepository->updateOrCreateActivity(
                $userId,
                $gameId,
                $currentPage,
                $ipAddress,
                $userAgent
            );
            
            // Debug DESPUÉS de actualizar
            $savedGameId = $activity->getCurrentGame() ? $activity->getCurrentGame()->getId() : 'NULL';
            $savedGameName = $activity->getCurrentGame() ? $activity->getCurrentGame()->getName() : 'ninguno';
            error_log("✅ LISTENER - Actividad guardada - Usuario ID: $userId, Juego guardado: $savedGameName (ID: $savedGameId), Página: " . ($activity->getCurrentPage() ?: 'N/A'));
        } catch (\Exception $e) {
            // Log de errores
            error_log('❌ Error al actualizar actividad de usuario: ' . $e->getMessage());
            error_log('Stack trace: ' . $e->getTraceAsString());
        }
    }
}

