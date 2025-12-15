<?php

namespace App\Controller;

use App\Repository\UserActivityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(
        AuthenticationUtils $authenticationUtils, 
        Request $request,
        UserActivityRepository $activityRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        
        // Si el usuario está autenticado, verificar su estado
        if ($user) {
            // IMPORTANTE: Verificar primero si está baneado (tiene prioridad sobre kick)
            $userRepository = $entityManager->getRepository(\App\Entity\User::class);
            $freshUser = $userRepository->find($user->getId());
            
            if ($freshUser && !$freshUser->isActive()) {
                // Usuario está baneado, redirigir a página de baneo
                return $this->redirectToRoute('app_banned');
            }
            
            // Si no está baneado, verificar si fue kickeado
            $activity = $activityRepository->findByUser($user->getId());
            $isKicked = $activity && !$activity->isOnline();
            
            // Si fue kickeado, redirigir a la página de kicked
            if ($isKicked) {
                return $this->redirectToRoute('app_kicked');
            }
            
            // Si no está kickeado ni baneado y está autenticado, redirigir al home
            return $this->redirectToRoute('app_home');
        }
        
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        // Verificar parámetros de redirección
        $kicked = $request->query->get('kicked') === '1';
        $banned = $request->query->get('banned') === '1';

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
            'kicked' => $kicked,
            'banned' => $banned,
        ]);
    }

    #[Route(path: '/kicked', name: 'app_kicked')]
    public function kicked(
        Request $request,
        TokenStorageInterface $tokenStorage,
        UserActivityRepository $activityRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        
        // Si no hay usuario autenticado, redirigir al login
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // IMPORTANTE: Verificar primero si está baneado (tiene prioridad sobre kick)
        $userRepository = $entityManager->getRepository(\App\Entity\User::class);
        $freshUser = $userRepository->find($user->getId());
        
        if ($freshUser && !$freshUser->isActive()) {
            // Usuario está baneado, redirigir a página de baneo
            return $this->redirectToRoute('app_banned');
        }

        // Si no está baneado, verificar si el usuario realmente fue kickeado (offline pero autenticado)
        $activity = $activityRepository->findByUser($user->getId());
        $isKicked = $activity && !$activity->isOnline();
        
        // Si no está kickeado, redirigir al home
        if (!$isKicked) {
            return $this->redirectToRoute('app_home');
        }

        // Invalidar la sesión del usuario
        $tokenStorage->setToken(null);
        $request->getSession()->invalidate();

        return $this->render('security/kicked.html.twig', [
            'username' => $user->getUserIdentifier(),
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
