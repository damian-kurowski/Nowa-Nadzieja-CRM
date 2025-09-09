<?php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\Table(name: 'activity_logs')]
#[ORM\Index(name: 'idx_user_created', columns: ['user_id', 'created_at'])]
#[ORM\Index(name: 'idx_created_at', columns: ['created_at'])]
class ActivityLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $action;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $entityType = null;

    #[ORM\Column(nullable: true)]
    private ?int $entityId = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $userAgent = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getEntityType(): ?string
    {
        return $this->entityType;
    }

    public function setEntityType(?string $entityType): static
    {
        $this->entityType = $entityType;

        return $this;
    }

    public function getEntityId(): ?int
    {
        return $this->entityId;
    }

    public function setEntityId(?int $entityId): static
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

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

    public function getFormattedTimeAgo(): string
    {
        $now = new \DateTime();
        $diff = $now->diff($this->createdAt);

        if ($diff->days > 0) {
            return $diff->days.' dzień'.($diff->days > 1 ? ($diff->days < 5 ? 'i' : 'i') : '').' temu';
        } elseif ($diff->h > 0) {
            return $diff->h.' godz'.($diff->h > 1 ? '.' : '.').' temu';
        } elseif ($diff->i > 0) {
            return $diff->i.' min. temu';
        } else {
            return 'przed chwilą';
        }
    }

    public function getBadgeClass(): string
    {
        return match ($this->action) {
            'create' => 'bg-success',
            'update' => 'bg-info',
            'delete' => 'bg-danger',
            'login' => 'bg-primary',
            'document_sign' => 'bg-warning',
            'document_create' => 'bg-success',
            'media_create' => 'bg-purple',
            'conference_create' => 'bg-purple',
            default => 'bg-secondary',
        };
    }

    public function getBadgeText(): string
    {
        return match ($this->action) {
            'create' => 'Utworzono',
            'update' => 'Aktualizacja',
            'delete' => 'Usunięto',
            'login' => 'Logowanie',
            'document_sign' => 'Podpis',
            'document_create' => 'Dokument',
            'media_create' => 'Media',
            'conference_create' => 'Konferencja',
            default => 'Akcja',
        };
    }
}
