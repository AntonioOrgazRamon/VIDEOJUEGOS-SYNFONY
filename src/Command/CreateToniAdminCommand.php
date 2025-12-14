<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:create-toni-admin',
    description: 'Crea el usuario admin toni',
)]
class CreateToniAdminCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = 'toni@toni.com';
        $username = 'toni';
        $plainPassword = 'tonitoni';
        
        // Verificar si el usuario ya existe
        $userRepository = $this->entityManager->getRepository(User::class);
        $existingUser = $userRepository->findOneBy(['email' => $email]);
        
        if ($existingUser) {
            // Actualizar el usuario existente
            $user = $existingUser;
            $output->writeln('Usuario existente encontrado. Actualizando...');
        } else {
            $user = new User();
            $output->writeln('Creando nuevo usuario...');
        }
        
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        
        // Encriptación de la contraseña
        $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);
        
        $user->setIsActive(true);
        $user->setStatus('active');
        $user->setVisibility('public');
        
        // Guarda el registro en la base de datos
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $output->writeln('');
        $output->writeln('✅ Usuario admin creado/actualizado correctamente:');
        $output->writeln("   Email: {$email}");
        $output->writeln("   Username: {$username}");
        $output->writeln("   Password: {$plainPassword}");
        $output->writeln("   Roles: ROLE_USER, ROLE_ADMIN");
        $output->writeln('');
        
        return Command::SUCCESS;
    }
}
