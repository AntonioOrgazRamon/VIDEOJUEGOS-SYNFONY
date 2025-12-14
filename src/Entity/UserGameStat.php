<?php

namespace App\Entity;

use App\Repository\UserGameStatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserGameStatRepository::class)]
#[ORM\Table(name: 'user_game_stats')]
#[ORM\UniqueConstraint(name: 'uq_stats_user_game', fields: ['userId', 'gameId'])]
#[ORM\Index(name: 'idx_stats_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_stats_game', columns: ['game_id'])]
#[ORM\Index(name: 'idx_stats_best', columns: ['game_id', 'best_score'])]
#[ORM\Index(name: 'idx_stats_plays', columns: ['user_id', 'plays_count'])]
class UserGameStat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'user_id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $userId = null;

    #[ORM\Column(name: 'game_id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $gameId = null;

    #[ORM\Column(name: 'plays_count', type: 'integer', options: ['default' => 0])]
    private int $playsCount = 0;

    #[ORM\Column(name: 'best_score', type: 'integer', options: ['default' => 0])]
    private int $bestScore = 0;

    #[ORM\Column(name: 'last_played_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastPlayedAt = null;

    #[ORM\Column(name: 'total_duration', type: 'integer', nullable: true)]
    private ?int $totalDuration = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Game $game = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
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

    public function getPlaysCount(): int
    {
        return $this->playsCount;
    }

    public function setPlaysCount(int $playsCount): static
    {
        $this->playsCount = $playsCount;

        return $this;
    }

    public function getBestScore(): int
    {
        return $this->bestScore;
    }

    public function setBestScore(int $bestScore): static
    {
        $this->bestScore = $bestScore;

        return $this;
    }

    public function getLastPlayedAt(): ?\DateTimeInterface
    {
        return $this->lastPlayedAt;
    }

    public function setLastPlayedAt(?\DateTimeInterface $lastPlayedAt): static
    {
        $this->lastPlayedAt = $lastPlayedAt;

        return $this;
    }

    public function getTotalDuration(): ?int
    {
        return $this->totalDuration;
    }

    public function setTotalDuration(?int $totalDuration): static
    {
        $this->totalDuration = $totalDuration;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        if ($user) {
            $this->userId = $user->getId();
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
        if ($game) {
            $this->gameId = $game->getId();
        }

        return $this;
    }
}



