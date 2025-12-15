<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:unban-user',
    description: 'Desbanea un usuario por email',
)]
class UnbanUserCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email del usuario a desbanear');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $email = $input->getArgument('email');
        
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);
        
        if (!$user) {
            $io->error("Usuario con email '{$email}' no encontrado.");
            return Command::FAILURE;
        }
        
        if ($user->isActive()) {
            $io->warning("El usuario '{$email}' ya está activo (no está baneado).");
            $io->info("Estado actual: Activo");
            return Command::SUCCESS;
        }
        
        // Desbanear usuario
        $user->setIsActive(true);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        $io->success("Usuario desbaneado correctamente:");
        $io->table(
            ['Campo', 'Valor'],
            [
                ['Email', $user->getEmail()],
                ['Username', $user->getUsername()],
                ['Estado', $user->isActive() ? 'Activo ✅' : 'Baneado ❌'],
                ['ID', $user->getId()],
            ]
        );
        
        return Command::SUCCESS;
    }
}


