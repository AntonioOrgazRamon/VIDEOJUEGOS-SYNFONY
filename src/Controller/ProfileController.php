<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ProfileController extends AbstractController
{
    #[Route('/profile/update-status', name: 'app_profile_update_status', methods: ['POST'])]
    public function updateStatus(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $status = $data['status'] ?? null;

        // Validar que el estado sea válido
        $validStatuses = ['active', 'away', 'offline'];
        if (!in_array($status, $validStatuses)) {
            return new JsonResponse(['success' => false, 'message' => 'Estado no válido'], 400);
        }

        // Obtener el usuario desde el repositorio para asegurar que está gestionado por Doctrine
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($currentUser->getId());
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Actualizar el estado
        $user->setStatus($status);
        $user->setLastSeenAt(new \DateTime());
        
        // Asegurar que el usuario está siendo gestionado por Doctrine
        $entityManager->persist($user);
        $entityManager->flush();
        
        // Verificar que se guardó correctamente
        $entityManager->clear();
        $savedUser = $userRepository->find($currentUser->getId());
        if (!$savedUser || $savedUser->getStatus() !== $status) {
            return new JsonResponse(['success' => false, 'message' => 'Error al guardar el estado en la base de datos'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'status' => $status,
            'message' => 'Estado actualizado correctamente'
        ]);
    }

    #[Route('/profile/upload-image', name: 'app_profile_upload_image', methods: ['POST'])]
    public function uploadImage(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }
        
        // Obtener el usuario desde el repositorio para asegurar que está gestionado por Doctrine
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($currentUser->getId());
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        $file = $request->files->get('profile_image');
        
        if (!$file) {
            return new JsonResponse(['success' => false, 'message' => 'No se ha subido ningún archivo'], 400);
        }

        // Validar tipo de archivo
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            return new JsonResponse(['success' => false, 'message' => 'Tipo de archivo no válido. Solo se permiten imágenes (JPEG, PNG, GIF, WebP)'], 400);
        }

        // Validar tamaño (máximo 5MB)
        if ($file->getSize() > 5 * 1024 * 1024) {
            return new JsonResponse(['success' => false, 'message' => 'El archivo es demasiado grande. Máximo 5MB'], 400);
        }

        // Generar nombre único para el archivo
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

        // Directorio donde se guardarán las imágenes
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/profiles/';
        
        // Crear directorio si no existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        try {
            // Mover el archivo al directorio de destino
            $file->move($uploadDir, $newFilename);
            
            // Eliminar la imagen anterior si existe y no es la predeterminada
            $oldImage = $user->getProfileImage();
            if ($oldImage && $oldImage !== 'images/default_profile.png' && file_exists($this->getParameter('kernel.project_dir') . '/public/' . $oldImage)) {
                unlink($this->getParameter('kernel.project_dir') . '/public/' . $oldImage);
            }
            
            // Actualizar la ruta en la base de datos
            $imagePath = 'images/profiles/' . $newFilename;
            $user->setProfileImage($imagePath);
            $entityManager->persist($user);
            $entityManager->flush();
            
            // Verificar que se guardó correctamente
            $entityManager->clear();
            $savedUser = $userRepository->find($currentUser->getId());
            if (!$savedUser || $savedUser->getProfileImage() !== $imagePath) {
                return new JsonResponse(['success' => false, 'message' => 'Error al guardar la imagen en la base de datos'], 500);
            }

            return new JsonResponse([
                'success' => true,
                'image_path' => '/' . $imagePath,
                'message' => 'Imagen de perfil actualizada correctamente'
            ]);
        } catch (FileException $e) {
            return new JsonResponse(['success' => false, 'message' => 'Error al subir la imagen: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/profile/change-username', name: 'app_profile_change_username', methods: ['POST'])]
    public function changeUsername(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $currentPassword = $data['current_password'] ?? null;
        $newUsername = $data['new_username'] ?? null;

        if (!$currentPassword || !$newUsername) {
            return new JsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
        }

        // Obtener el usuario desde el repositorio
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($currentUser->getId());
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Verificar contraseña actual
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return new JsonResponse(['success' => false, 'message' => 'Contraseña actual incorrecta'], 400);
        }

        // Validar nuevo nombre de usuario
        if (strlen($newUsername) > 60) {
            return new JsonResponse(['success' => false, 'message' => 'El nombre de usuario no puede tener más de 60 caracteres'], 400);
        }

        // Normalizar el nombre de usuario (trim)
        $newUsername = trim($newUsername);
        $currentUsername = trim($user->getUsername());
        
        // Si el nuevo nombre es el mismo que el actual (case-insensitive), no hay nada que cambiar
        if (strtolower($currentUsername) === strtolower($newUsername)) {
            return new JsonResponse([
                'success' => true,
                'message' => 'El nombre de usuario ya es el mismo'
            ]);
        }

        // No necesitamos verificar si el nombre está en uso por otros usuarios
        // Se permiten nombres de usuario duplicados siempre que tengan IDs diferentes
        // Solo verificamos que el nuevo nombre sea diferente al actual

        // Actualizar el nombre de usuario
        $user->setUsername($newUsername);
        $entityManager->persist($user);
        $entityManager->flush();
        
        // Verificar que se guardó correctamente
        $entityManager->clear();
        $savedUser = $userRepository->find($currentUser->getId());
        if (!$savedUser || $savedUser->getUsername() !== $newUsername) {
            return new JsonResponse(['success' => false, 'message' => 'Error al guardar el nombre de usuario en la base de datos'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'username' => $newUsername,
            'message' => 'Nombre de usuario actualizado correctamente'
        ]);
    }

    #[Route('/profile/change-email', name: 'app_profile_change_email', methods: ['POST'])]
    public function changeEmail(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $currentPassword = $data['current_password'] ?? null;
        $newEmail = $data['new_email'] ?? null;

        if (!$currentPassword || !$newEmail) {
            return new JsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
        }

        // Validar formato de email
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['success' => false, 'message' => 'El correo no es válido'], 400);
        }

        // Obtener el usuario desde el repositorio
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($currentUser->getId());
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Verificar contraseña actual
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return new JsonResponse(['success' => false, 'message' => 'Contraseña actual incorrecta'], 400);
        }

        // Verificar que el nuevo correo no esté en uso
        $existingUser = $userRepository->findOneBy(['email' => $newEmail]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            return new JsonResponse(['success' => false, 'message' => 'Este correo ya está en uso'], 400);
        }

        // Actualizar el correo
        $user->setEmail($newEmail);
        $entityManager->persist($user);
        $entityManager->flush();
        
        // Verificar que se guardó correctamente
        $entityManager->clear();
        $savedUser = $userRepository->find($currentUser->getId());
        if (!$savedUser || $savedUser->getEmail() !== $newEmail) {
            return new JsonResponse(['success' => false, 'message' => 'Error al guardar el correo en la base de datos'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'email' => $newEmail,
            'message' => 'Correo actualizado correctamente'
        ]);
    }

    #[Route('/profile/change-password', name: 'app_profile_change_password', methods: ['POST'])]
    public function changePassword(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $currentPassword = $data['current_password'] ?? null;
        $newPassword = $data['new_password'] ?? null;

        if (!$currentPassword || !$newPassword) {
            return new JsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
        }

        // Validar longitud de la nueva contraseña
        if (strlen($newPassword) < 6) {
            return new JsonResponse(['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'], 400);
        }

        // Obtener el usuario desde el repositorio
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($currentUser->getId());
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Verificar contraseña actual
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return new JsonResponse(['success' => false, 'message' => 'Contraseña actual incorrecta'], 400);
        }

        // Actualizar la contraseña
        $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        $entityManager->persist($user);
        $entityManager->flush();
        
        // Verificar que se guardó correctamente
        $entityManager->clear();
        $savedUser = $userRepository->find($currentUser->getId());
        if (!$savedUser) {
            return new JsonResponse(['success' => false, 'message' => 'Error al guardar la contraseña en la base de datos'], 500);
        }
        
        // Verificar que la contraseña se guardó correctamente
        if (!$passwordHasher->isPasswordValid($savedUser, $newPassword)) {
            return new JsonResponse(['success' => false, 'message' => 'Error: La contraseña no se guardó correctamente'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ]);
    }

    #[Route('/profile/change-theme', name: 'app_profile_change_theme', methods: ['POST'])]
    public function changeTheme(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $themeMode = $data['theme_mode'] ?? null;

        // Validar que el tema sea válido
        $validThemes = ['light', 'dark'];
        if (!in_array($themeMode, $validThemes)) {
            return new JsonResponse(['success' => false, 'message' => 'Tema no válido'], 400);
        }

        // Obtener el usuario desde el repositorio
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($currentUser->getId());
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Actualizar el tema
        $user->setThemeMode($themeMode);
        $entityManager->persist($user);
        $entityManager->flush();
        
        // Verificar que se guardó correctamente
        $entityManager->clear();
        $savedUser = $userRepository->find($currentUser->getId());
        if (!$savedUser || $savedUser->getThemeMode() !== $themeMode) {
            return new JsonResponse(['success' => false, 'message' => 'Error al guardar el tema en la base de datos'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'theme_mode' => $themeMode,
            'message' => 'Tema actualizado correctamente'
        ]);
    }

    #[Route('/profile/change-language', name: 'app_profile_change_language', methods: ['POST'])]
    public function changeLanguage(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $currentUser = $this->getUser();
        
        if (!$currentUser) {
            return new JsonResponse(['success' => false, 'message' => 'Debes estar autenticado'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $language = $data['language'] ?? null;

        // Validar que el idioma sea válido
        $validLanguages = ['es', 'en'];
        if (!in_array($language, $validLanguages)) {
            return new JsonResponse(['success' => false, 'message' => 'Idioma no válido'], 400);
        }

        // Obtener el usuario desde el repositorio
        $userRepository = $entityManager->getRepository(User::class);
        $user = $userRepository->find($currentUser->getId());
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Actualizar el idioma
        $user->setLanguage($language);
        $entityManager->persist($user);
        $entityManager->flush();
        
        // Verificar que se guardó correctamente
        $entityManager->clear();
        $savedUser = $userRepository->find($currentUser->getId());
        if (!$savedUser || $savedUser->getLanguage() !== $language) {
            return new JsonResponse(['success' => false, 'message' => 'Error al guardar el idioma en la base de datos'], 500);
        }

        return new JsonResponse([
            'success' => true,
            'language' => $language,
            'message' => 'Idioma actualizado correctamente'
        ]);
    }
}

