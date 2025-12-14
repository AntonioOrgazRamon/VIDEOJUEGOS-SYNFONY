<?php

namespace App\Entity;

use App\Repository\UserSessionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSessionRepository::class)]
#[ORM\Table(name: 'user_sessions')]
#[ORM\UniqueConstraint(name: 'uq_user_sessions_token', fields: ['sessionToken'])]
#[ORM\Index(name: 'idx_sessions_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_sessions_activity', columns: ['last_activity_at'])]
#[ORM\Index(name: 'idx_sessions_open', columns: ['ended_at', 'is_valid'])]
class UserSession
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'session_token', length: 255, unique: true)]
    private ?string $sessionToken = null;

    #[ORM\Column(name: 'ip', length: 45, nullable: true)]
    private ?string $ip = null;

    #[ORM\Column(name: 'user_agent', length: 255, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'last_activity_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $lastActivityAt = null;

    #[ORM\Column(name: 'ended_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endedAt = null;

    #[ORM\Column(name: 'is_valid', type: 'boolean', options: ['default' => true])]
    private bool $isValid = true;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

        return $this;
    }

    public function getSessionToken(): ?string
    {
        return $this->sessionToken;
    }

    public function setSessionToken(string $sessionToken): static
    {
        $this->sessionToken = $sessionToken;

        return $this;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(?string $ip): static
    {
        $this->ip = $ip;

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

    public function getLastActivityAt(): ?\DateTimeInterface
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeInterface $lastActivityAt): static
    {
        $this->lastActivityAt = $lastActivityAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeInterface
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeInterface $endedAt): static
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): static
    {
        $this->isValid = $isValid;

        return $this;
    }
}

