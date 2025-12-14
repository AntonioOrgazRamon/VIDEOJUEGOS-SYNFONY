<?php

namespace App\Entity;

use App\Repository\UserPresenceRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserPresenceRepository::class)]
#[ORM\Table(name: 'user_presence')]
#[ORM\UniqueConstraint(name: 'uq_presence_user', fields: ['user'])]
#[ORM\Index(name: 'idx_presence_state', columns: ['state'])]
#[ORM\Index(name: 'idx_presence_game', columns: ['current_game_id'])]
class UserPresence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE', unique: true)]
    private ?User $user = null;

    #[ORM\Column(name: 'state', type: 'string', length: 20, options: ['default' => 'active'])]
    private string $state = 'active';

    #[ORM\Column(name: 'current_page', length: 255, nullable: true)]
    private ?string $currentPage = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(name: 'current_game_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Game $currentGame = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP', 'columnDefinition' => 'DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
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

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getCurrentPage(): ?string
    {
        return $this->currentPage;
    }

    public function setCurrentPage(?string $currentPage): static
    {
        $this->currentPage = $currentPage;

        return $this;
    }

    public function getCurrentGame(): ?Game
    {
        return $this->currentGame;
    }

    public function setCurrentGame(?Game $currentGame): static
    {
        $this->currentGame = $currentGame;

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
}

