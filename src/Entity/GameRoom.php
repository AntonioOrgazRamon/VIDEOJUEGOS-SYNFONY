<?php

namespace App\Entity;

use App\Repository\GameRoomRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRoomRepository::class)]
#[ORM\Table(name: 'game_rooms')]
#[ORM\Index(name: 'idx_game_rooms_game', columns: ['game_id'])]
#[ORM\Index(name: 'idx_game_rooms_status', columns: ['status'])]
#[ORM\Index(name: 'idx_game_rooms_created', columns: ['created_at'])]
class GameRoom
{
    public const STATUS_WAITING = 'waiting';
    public const STATUS_PLAYING = 'playing';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Game $game = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'player1_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $player1 = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'player2_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    private ?User $player2 = null;

    #[ORM\Column(name: 'status', type: 'string', length: 20, options: ['default' => 'waiting'])]
    private string $status = self::STATUS_WAITING;

    #[ORM\Column(name: 'current_turn', type: 'bigint', nullable: true)]
    private ?int $currentTurn = null;

    #[ORM\Column(name: 'game_state', type: Types::JSON, nullable: true)]
    private ?array $gameState = null;

    #[ORM\Column(name: 'winner_id', type: 'bigint', nullable: true)]
    private ?int $winnerId = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(name: 'started_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(name: 'finished_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $finishedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;
        return $this;
    }

    public function getPlayer1(): ?User
    {
        return $this->player1;
    }

    public function setPlayer1(?User $player1): static
    {
        $this->player1 = $player1;
        if ($this->currentTurn === null) {
            $this->currentTurn = $player1->getId();
        }
        return $this;
    }

    public function getPlayer2(): ?User
    {
        return $this->player2;
    }

    public function setPlayer2(?User $player2): static
    {
        $this->player2 = $player2;
        if ($this->status === self::STATUS_WAITING && $player2 !== null) {
            $this->status = self::STATUS_PLAYING;
            $this->startedAt = new \DateTime();
        }
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTime();
        
        if ($status === self::STATUS_PLAYING && $this->startedAt === null) {
            $this->startedAt = new \DateTime();
        }
        
        if ($status === self::STATUS_FINISHED && $this->finishedAt === null) {
            $this->finishedAt = new \DateTime();
        }
        
        return $this;
    }

    public function getCurrentTurn(): ?int
    {
        return $this->currentTurn;
    }

    public function setCurrentTurn(?int $currentTurn): static
    {
        $this->currentTurn = $currentTurn;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getGameState(): ?array
    {
        return $this->gameState;
    }

    public function setGameState(?array $gameState): static
    {
        $this->gameState = $gameState;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getWinnerId(): ?int
    {
        return $this->winnerId;
    }

    public function setWinnerId(?int $winnerId): static
    {
        $this->winnerId = $winnerId;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): static
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getFinishedAt(): ?\DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeInterface $finishedAt): static
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    /**
     * Verifica si un usuario es parte de esta sala
     */
    public function hasPlayer(User $user): bool
    {
        return ($this->player1 && $this->player1->getId() === $user->getId()) ||
               ($this->player2 && $this->player2->getId() === $user->getId());
    }

    /**
     * Obtiene el oponente de un usuario
     */
    public function getOpponent(User $user): ?User
    {
        if ($this->player1 && $this->player1->getId() === $user->getId()) {
            return $this->player2;
        }
        if ($this->player2 && $this->player2->getId() === $user->getId()) {
            return $this->player1;
        }
        return null;
    }

    /**
     * Verifica si es el turno de un usuario
     */
    public function isPlayerTurn(User $user): bool
    {
        return $this->currentTurn === $user->getId();
    }

    /**
     * Cambia el turno al siguiente jugador
     */
    public function switchTurn(): void
    {
        if ($this->player1 && $this->player2) {
            $this->currentTurn = $this->currentTurn === $this->player1->getId() 
                ? $this->player2->getId() 
                : $this->player1->getId();
            $this->updatedAt = new \DateTime();
        }
    }

    /**
     * Verifica si la sala está esperando jugadores
     */
    public function isWaiting(): bool
    {
        return $this->status === self::STATUS_WAITING;
    }

    /**
     * Verifica si la sala está en juego
     */
    public function isPlaying(): bool
    {
        return $this->status === self::STATUS_PLAYING;
    }
}


