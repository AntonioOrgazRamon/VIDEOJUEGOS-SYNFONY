<?php

namespace App\Controller;

use App\Entity\PasswordReset;
use App\Entity\User;
use App\Repository\PasswordResetRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class PasswordResetController extends AbstractController
{
    private const CODE_LENGTH = 6;
    private const CODE_EXPIRY_MINUTES = 5;
    private const MAX_ATTEMPTS = 5;

    #[Route('/password-reset/request', name: 'app_password_reset_request', methods: ['GET', 'POST'])]
    public function requestReset(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        PasswordResetRepository $passwordResetRepository
    ): Response|JsonResponse {
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true);
            $email = $data['email'] ?? '';

            if (empty($email)) {
                return new JsonResponse(['success' => false, 'message' => 'El email es requerido'], 400);
            }

            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                // Por seguridad, no revelamos si el email existe o no
                return new JsonResponse([
                    'success' => true,
                    'message' => 'Si el email existe, recibirás un código de verificación'
                ]);
            }

            // Invalidar todos los códigos anteriores no usados de este usuario
            $existingResets = $passwordResetRepository->findActiveResetsByUser($user->getId());
            foreach ($existingResets as $reset) {
                $reset->setUsedAt(new \DateTime());
                $entityManager->persist($reset);
            }

            // Generar código de 6 dígitos
            $code = str_pad((string)random_int(0, 999999), self::CODE_LENGTH, '0', STR_PAD_LEFT);

            // Hashear el código
            $codeHash = password_hash($code, PASSWORD_BCRYPT);

            // Crear nuevo reset
            $passwordReset = new PasswordReset();
            $passwordReset->setUser($user);
            $passwordReset->setCodeHash($codeHash);
            $passwordReset->setExpiresAt(new \DateTime('+' . self::CODE_EXPIRY_MINUTES . ' minutes'));
            $passwordReset->setAttempts(0);

            $entityManager->persist($passwordReset);
            $entityManager->flush();

            // Enviar email con el código
            $emailSent = false;
            $isDevMode = $this->getParameter('kernel.environment') === 'dev';
            
            try {
                // Verificar si hay configuración de email
                $mailerDsn = $_ENV['MAILER_DSN'] ?? null;
                
                // Solo intentar enviar si hay configuración real (no null://null)
                if ($mailerDsn && $mailerDsn !== 'null://null' && strpos($mailerDsn, 'null://') === false) {
                    $fromEmail = $_ENV['MAILER_FROM'] ?? 'noreply@plataformajuegos.com';
                    $emailMessage = (new Email())
                        ->from($fromEmail)
                        ->to($user->getEmail())
                        ->subject('Código de recuperación de contraseña')
                        ->html($this->renderView('email/password_reset_code.html.twig', [
                            'code' => $code,
                            'username' => $user->getUsername(),
                            'expiryMinutes' => self::CODE_EXPIRY_MINUTES
                        ]));

                    $mailer->send($emailMessage);
                    $emailSent = true;
                } else {
                    // No hay email configurado, no intentar enviar
                    $emailSent = false;
                }
            } catch (\Exception $e) {
                // Log del error
                error_log('Error enviando email de reset: ' . $e->getMessage());
                
                // En modo desarrollo, loguear el código para debugging
                if ($isDevMode) {
                    error_log('=== CÓDIGO DE RESET (DEV MODE) ===');
                    error_log('Email: ' . $user->getEmail());
                    error_log('Código: ' . $code);
                    error_log('Reset ID: ' . $passwordReset->getId());
                    error_log('===================================');
                }
            }

            // Preparar respuesta
            $response = [
                'success' => true,
                'message' => 'Si el email existe, recibirás un código de verificación',
                'resetId' => $passwordReset->getId()
            ];
            
            // En modo desarrollo, SIEMPRE incluir el código para testing (incluso si el email se envió)
            if ($isDevMode) {
                $response['dev_mode'] = true;
                $response['code'] = $code;
                if (!$emailSent) {
                    $response['message'] = '⚠️ MODO DESARROLLO: El email no está configurado. Usa este código: ' . $code;
                } else {
                    $response['message'] = '✅ Email enviado (modo desarrollo). Código para testing: ' . $code;
                }
            }

            return new JsonResponse($response);
        }

        return $this->render('security/password_reset_request.html.twig');
    }

    #[Route('/password-reset/verify', name: 'app_password_reset_verify', methods: ['GET', 'POST'])]
    public function verifyCode(
        Request $request,
        PasswordResetRepository $passwordResetRepository,
        EntityManagerInterface $entityManager
    ): Response|JsonResponse {
        if ($request->isMethod('GET')) {
            $resetId = $request->query->get('resetId');
            if (!$resetId) {
                return $this->redirectToRoute('app_password_reset_request');
            }
            return $this->render('security/password_reset_verify.html.twig', [
                'resetId' => $resetId
            ]);
        }
        $data = json_decode($request->getContent(), true);
        $resetId = $data['resetId'] ?? null;
        $code = $data['code'] ?? '';

        if (empty($resetId) || empty($code)) {
            return new JsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
        }

        $passwordReset = $passwordResetRepository->find($resetId);

        if (!$passwordReset) {
            return new JsonResponse(['success' => false, 'message' => 'Código no válido'], 404);
        }

        // Verificar si ya fue usado
        if ($passwordReset->getUsedAt() !== null) {
            return new JsonResponse(['success' => false, 'message' => 'Este código ya fue utilizado'], 400);
        }

        // Verificar si expiró
        if ($passwordReset->getExpiresAt() < new \DateTime()) {
            return new JsonResponse(['success' => false, 'message' => 'El código ha expirado'], 400);
        }

        // Verificar intentos
        if ($passwordReset->getAttempts() >= self::MAX_ATTEMPTS) {
            return new JsonResponse(['success' => false, 'message' => 'Demasiados intentos fallidos. Solicita un nuevo código'], 400);
        }

        // Verificar el código
        if (!password_verify($code, $passwordReset->getCodeHash())) {
            $passwordReset->setAttempts($passwordReset->getAttempts() + 1);
            $entityManager->persist($passwordReset);
            $entityManager->flush();

            $remainingAttempts = self::MAX_ATTEMPTS - $passwordReset->getAttempts();
            return new JsonResponse([
                'success' => false,
                'message' => 'Código incorrecto. Intentos restantes: ' . $remainingAttempts
            ], 400);
        }

        // Código correcto - marcar como usado pero no eliminar (para auditoría)
        $passwordReset->setUsedAt(new \DateTime());
        $entityManager->persist($passwordReset);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Código verificado correctamente',
            'resetId' => $passwordReset->getId()
        ]);
    }

    #[Route('/password-reset/reset', name: 'app_password_reset_reset', methods: ['GET', 'POST'])]
    public function resetPassword(
        Request $request,
        PasswordResetRepository $passwordResetRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response|JsonResponse {
        if ($request->isMethod('GET')) {
            $resetId = $request->query->get('resetId');
            if (!$resetId) {
                return $this->redirectToRoute('app_password_reset_request');
            }
            
            // Verificar que el reset existe y fue verificado
            $passwordReset = $passwordResetRepository->find($resetId);
            if (!$passwordReset || $passwordReset->getUsedAt() === null) {
                return $this->redirectToRoute('app_password_reset_request');
            }
            
            return $this->render('security/password_reset_reset.html.twig', [
                'resetId' => $resetId
            ]);
        }
        $data = json_decode($request->getContent(), true);
        $resetId = $data['resetId'] ?? null;
        $newPassword = $data['newPassword'] ?? '';

        if (empty($resetId) || empty($newPassword)) {
            return new JsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
        }

        if (strlen($newPassword) < 6) {
            return new JsonResponse(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        }

        $passwordReset = $passwordResetRepository->find($resetId);

        if (!$passwordReset) {
            return new JsonResponse(['success' => false, 'message' => 'Solicitud no válida'], 404);
        }

        // Verificar que el código fue verificado (usedAt no es null)
        if ($passwordReset->getUsedAt() === null) {
            return new JsonResponse(['success' => false, 'message' => 'Debes verificar el código primero'], 400);
        }

        // Verificar que no haya pasado mucho tiempo desde la verificación (5 minutos adicionales)
        $verificationTime = $passwordReset->getUsedAt();
        $maxTimeAfterVerification = clone $verificationTime;
        $maxTimeAfterVerification->modify('+5 minutes');
        if (new \DateTime() > $maxTimeAfterVerification) {
            return new JsonResponse(['success' => false, 'message' => 'Tiempo de verificación expirado. Solicita un nuevo código'], 400);
        }

        $user = $passwordReset->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Cambiar la contraseña
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    }
}

