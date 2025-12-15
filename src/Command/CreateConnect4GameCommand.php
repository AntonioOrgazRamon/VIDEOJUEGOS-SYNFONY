<?php

namespace App\Command;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-connect4-game',
    description: 'Crea el juego de 4 en raya en la base de datos'
)]
class CreateConnect4GameCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $gameRepository = $this->entityManager->getRepository(Game::class);
        $existingGame = $gameRepository->findOneBy(['slug' => '4-en-raya']);

        if ($existingGame) {
            $io->warning('El juego de 4 en raya ya existe en la base de datos.');
            return Command::SUCCESS;
        }

        $game = new Game();
        $game->setName('4 en Raya');
        $game->setSlug('4-en-raya');
        $game->setDescription('Juego multijugador de 4 en raya. Conecta 4 fichas en línea para ganar!');
        $game->setIcon('icons/connect4.png');
        $game->setIsActive(true);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        $io->success(sprintf('Juego de 4 en raya creado con ID: %d', $game->getId()));

        return Command::SUCCESS;
    }
}


