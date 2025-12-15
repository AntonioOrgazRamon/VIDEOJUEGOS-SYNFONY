<?php

namespace App\Entity;

use App\Repository\UserBanHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserBanHistoryRepository::class)]
#[ORM\Table(name: 'user_ban_history')]
#[ORM\Index(name: 'idx_ban_history_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_ban_history_type', columns: ['action_type'])]
#[ORM\Index(name: 'idx_ban_history_created', columns: ['created_at'])]
class UserBanHistory
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

    #[ORM\Column(name: 'action_type', type: 'string', length: 20)]
    private ?string $actionType = null; // 'ban' o 'kick' o 'unban'

    #[ORM\Column(name: 'message', type: Types::TEXT, nullable: true)]
    private ?string $message = null;

    #[ORM\Column(name: 'performed_by', type: 'bigint', nullable: true, options: ['unsigned' => true])]
    private ?int $performedBy = null;

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

    public function getActionType(): ?string
    {
        return $this->actionType;
    }

    public function setActionType(string $actionType): static
    {
        $this->actionType = $actionType;
        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message): static
    {
        $this->message = $message;
        return $this;
    }

    public function getPerformedBy(): ?int
    {
        return $this->performedBy;
    }

    public function setPerformedBy(?int $performedBy): static
    {
        $this->performedBy = $performedBy;
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


