<?php

namespace App\Entity;

use App\Repository\PasswordResetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PasswordResetRepository::class)]
#[ORM\Table(name: 'password_resets')]
#[ORM\Index(name: 'idx_pwreset_user_created', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'idx_pwreset_expires', columns: ['expires_at'])]
#[ORM\Index(name: 'idx_pwreset_used', columns: ['used_at'])]
class PasswordReset
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'bigint', options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'code_hash', length: 255)]
    private ?string $codeHash = null;

    #[ORM\Column(name: 'expires_at', type: 'datetime')]
    private ?\DateTimeInterface $expiresAt = null;

    #[ORM\Column(name: 'attempts', type: 'integer', options: ['default' => 0])]
    private int $attempts = 0;

    #[ORM\Column(name: 'used_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $usedAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime', options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getCodeHash(): ?string
    {
        return $this->codeHash;
    }

    public function setCodeHash(string $codeHash): static
    {
        $this->codeHash = $codeHash;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function setAttempts(int $attempts): static
    {
        $this->attempts = $attempts;

        return $this;
    }

    public function getUsedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTimeInterface $usedAt): static
    {
        $this->usedAt = $usedAt;

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
}

