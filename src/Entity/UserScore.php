<?php

namespace App\Entity;

use App\Repository\UserScoreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserScoreRepository::class)]
#[ORM\Table(name: 'user_scores')]
#[ORM\Index(name: 'idx_scores_game_score', columns: ['game_id', 'score'])]
#[ORM\Index(name: 'idx_scores_user_game', columns: ['user_id', 'game_id'])]
#[ORM\Index(name: 'idx_scores_played_at', columns: ['played_at'])]
class UserScore
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(name: 'game_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?Game $game = null;

    #[ORM\Column(name: 'score', type: 'integer')]
    private ?int $score = null;

    #[ORM\Column(name: 'played_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $playedAt = null;

    #[ORM\Column(name: 'duration', type: 'integer', nullable: true)]
    private ?int $duration = null;

    #[ORM\Column(name: 'level', type: 'integer', nullable: true)]
    private ?int $level = null;

    #[ORM\Column(name: 'extra_data', type: Types::JSON, nullable: true)]
    private ?array $extraData = null;

    public function __construct()
    {
        $this->playedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
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

    public function getScore(): ?int
    {
        return $this->score;
    }

    public function setScore(int $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getPlayedAt(): ?\DateTimeInterface
    {
        return $this->playedAt;
    }

    public function setPlayedAt(?\DateTimeInterface $playedAt): static
    {
        $this->playedAt = $playedAt;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(?int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getExtraData(): ?array
    {
        return $this->extraData;
    }

    public function setExtraData(?array $extraData): static
    {
        $this->extraData = $extraData;

        return $this;
    }
}

