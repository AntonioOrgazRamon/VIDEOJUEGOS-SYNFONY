<?php

namespace App\Entity;

use App\Repository\UserGameLikeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserGameLikeRepository::class)]
#[ORM\Table(name: 'user_game_likes')]
#[ORM\UniqueConstraint(name: 'uq_like_user_game', fields: ['userId', 'gameId'])]
#[ORM\Index(name: 'idx_like_game', columns: ['game_id'])]
#[ORM\Index(name: 'idx_like_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_like_created', columns: ['created_at'])]
class UserGameLike
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $userId = null;

    #[ORM\Column(name: 'game_id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $gameId = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: null)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: null)]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    private ?Game $game = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getGameId(): ?int
    {
        return $this->gameId;
    }

    public function setGameId(int $gameId): static
    {
        $this->gameId = $gameId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        if ($user !== null) {
            $this->userId = $user->getId();
        } else {
            $this->userId = null;
        }

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): static
    {
        $this->game = $game;
        if ($game !== null) {
            $this->gameId = $game->getId();
        } else {
            $this->gameId = null;
        }

        return $this;
    }
}

