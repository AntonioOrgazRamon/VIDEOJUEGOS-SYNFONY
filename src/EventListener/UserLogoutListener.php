<?php

namespace App\EventListener;

use App\Repository\UserActivityRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LogoutEvent;

#[AsEventListener(event: LogoutEvent::class)]
class UserLogoutListener
{
    public function __construct(
        private UserActivityRepository $activityRepository
    ) {
    }

    public function __invoke(LogoutEvent $event): void
    {
        $token = $event->getToken();
        if (!$token) {
            return;
        }

        $user = $token->getUser();
        
        if (!$user || !is_object($user) || !method_exists($user, 'getId')) {
            return;
        }

        try {
            $userId = $user->getId();
            
            // Marcar usuario como offline
            $this->activityRepository->markUserOffline($userId);
            
            if ($_ENV['APP_ENV'] === 'dev') {
                error_log("🔴 Usuario ID: $userId marcado como offline (logout)");
            }
        } catch (\Exception $e) {
            error_log('Error al marcar usuario como offline en logout: ' . $e->getMessage());
        }
    }
}

