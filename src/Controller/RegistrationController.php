<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        ValidatorInterface $validator
    ): Response {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $username = $request->request->get('username');
            $password = $request->request->get('password');
            $confirmPassword = $request->request->get('confirm_password');

            $errors = [];

            // Validaciones básicas
            if (empty($email)) {
                $errors[] = 'El email es obligatorio';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'El email no es válido';
            }

            if (empty($username)) {
                $errors[] = 'El nombre de usuario es obligatorio';
            } elseif (strlen($username) > 60) {
                $errors[] = 'El nombre de usuario no puede tener más de 60 caracteres';
            }

            if (empty($password)) {
                $errors[] = 'La contraseña es obligatoria';
            } elseif (strlen($password) < 6) {
                $errors[] = 'La contraseña debe tener al menos 6 caracteres';
            }

            if ($password !== $confirmPassword) {
                $errors[] = 'Las contraseñas no coinciden';
            }

            // Verificar si el email ya existe
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $errors[] = 'Este email ya está registrado';
            }

            // Verificar si el username ya existe
            $existingUsername = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
            if ($existingUsername) {
                $errors[] = 'Este nombre de usuario ya está en uso';
            }

            if (empty($errors)) {
                // Crear nuevo usuario
                $user = new User();
                $user->setEmail($email);
                $user->setUsername($username);
                $user->setRoles(['ROLE_USER']);
                $user->setIsActive(true);
                $user->setStatus('active');
                $user->setVisibility('public');

                // Hashear la contraseña
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);

                // Validar la entidad
                $validationErrors = $validator->validate($user);
                if (count($validationErrors) === 0) {
                    $entityManager->persist($user);
                    $entityManager->flush();

                    $this->addFlash('success', 'Registro exitoso. Ahora puedes iniciar sesión.');
                    return $this->redirectToRoute('app_login');
                } else {
                    foreach ($validationErrors as $error) {
                        $errors[] = $error->getMessage();
                    }
                }
            }

            return $this->render('security/register.html.twig', [
                'errors' => $errors,
                'email' => $email,
                'username' => $username,
            ]);
        }

        return $this->render('security/register.html.twig', [
            'errors' => [],
            'email' => '',
            'username' => '',
        ]);
    }
}

