<?php

namespace App\Entity;

use App\Repository\UserActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserActivityRepository::class)]
#[ORM\Table(name: 'user_activity')]
#[ORM\Index(name: 'idx_activity_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_activity_last_activity', columns: ['last_activity_at'])]
#[ORM\Index(name: 'idx_activity_online', columns: ['is_online'])]
class UserActivity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE', nullable: false)]
    private ?User $user = null;

    #[ORM\Column(name: 'user_id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $userId = null;

    #[ORM\ManyToOne(targetEntity: Game::class)]
    #[ORM\JoinColumn(name: 'current_game_id', referencedColumnName: 'id', onDelete: 'SET NULL', nullable: true)]
    private ?Game $currentGame = null;

    #[ORM\Column(name: 'current_game_id', type: 'bigint', nullable: true, options: ['unsigned' => true])]
    private ?int $currentGameId = null;

    #[ORM\Column(name: 'current_page', length: 255, nullable: true)]
    private ?string $currentPage = null;

    #[ORM\Column(name: 'last_activity_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $lastActivityAt = null;

    #[ORM\Column(name: 'is_online', type: 'boolean', options: ['default' => true])]
    private bool $isOnline = true;

    #[ORM\Column(name: 'ip_address', length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(name: 'user_agent', type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP', 'onUpdate' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->lastActivityAt = new \DateTime();
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
        if ($user !== null) {
            $this->userId = $user->getId();
        }
        return $this;
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

    public function getCurrentGame(): ?Game
    {
        return $this->currentGame;
    }

    public function setCurrentGame(?Game $currentGame): static
    {
        $this->currentGame = $currentGame;
        if ($currentGame !== null) {
            $this->currentGameId = $currentGame->getId();
        } else {
            $this->currentGameId = null;
        }
        return $this;
    }

    public function getCurrentGameId(): ?int
    {
        return $this->currentGameId;
    }

    public function setCurrentGameId(?int $currentGameId): static
    {
        $this->currentGameId = $currentGameId;
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

    public function getLastActivityAt(): ?\DateTimeInterface
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeInterface $lastActivityAt): static
    {
        $this->lastActivityAt = $lastActivityAt;
        return $this;
    }

    public function isOnline(): bool
    {
        return $this->isOnline;
    }

    public function setIsOnline(bool $isOnline): static
    {
        $this->isOnline = $isOnline;
        return $this;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress;
        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): static
    {
        $this->userAgent = $userAgent;
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

    /**
     * Actualiza la última actividad a ahora
     */
    public function updateActivity(): static
    {
        $this->lastActivityAt = new \DateTime();
        $this->updatedAt = new \DateTime();
        $this->isOnline = true;
        return $this;
    }
}
