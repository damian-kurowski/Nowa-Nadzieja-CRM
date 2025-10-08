<?php

namespace App\Entity;

use App\Repository\PrzekazMedialnyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrzekazMedialnyRepository::class)]
#[ORM\Table(name: 'przekaz_medialny')]
#[ORM\Index(columns: ['data_wyslania'], name: 'idx_przekaz_data_wyslania')]
#[ORM\Index(columns: ['status'], name: 'idx_przekaz_status')]
class PrzekazMedialny
{
    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENDING = 'sending';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    // Media type constants
    public const MEDIA_TYPE_PHOTO = 'photo';
    public const MEDIA_TYPE_VIDEO = 'video';

    // Limits
    public const MAX_CAPTION_LENGTH = 1024; // Telegram caption limit
    public const MAX_PHOTO_SIZE = 20 * 1024 * 1024; // 20MB
    public const MAX_VIDEO_SIZE = 50 * 1024 * 1024; // 50MB
    public const FLUSH_BATCH_SIZE = 50;

    // Allowed MIME types
    public const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif'];
    public const ALLOWED_VIDEO_TYPES = ['video/mp4', 'video/mpeg', 'video/quicktime'];


    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $tytul = null;

    #[ORM\Column(type: 'text')]
    private ?string $tresc = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mediaFilePath = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $mediaType = null; // 'photo' or 'video'

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $autor = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dataWyslania = null;

    #[ORM\Column]
    private int $liczbaOdbiorcow = 0;

    #[ORM\Column]
    private int $liczbaPrzeczytanych = 0;

    #[ORM\Column]
    private int $liczbaOdpowiedzi = 0;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dataUtworzenia = null;

    #[ORM\OneToMany(mappedBy: 'przekaz', targetEntity: PrzekazOdbiorca::class, cascade: ['persist', 'remove'])]
    private Collection $odbiorcy;

    #[ORM\OneToMany(mappedBy: 'przekaz', targetEntity: PrzekazOdpowiedz::class, cascade: ['persist', 'remove'])]
    private Collection $odpowiedzi;

    public function __construct()
    {
        $this->dataUtworzenia = new \DateTime();
        $this->odbiorcy = new ArrayCollection();
        $this->odpowiedzi = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTytul(): ?string
    {
        return $this->tytul;
    }

    public function setTytul(string $tytul): static
    {
        $this->tytul = $tytul;
        return $this;
    }

    public function getTresc(): ?string
    {
        return $this->tresc;
    }

    public function setTresc(string $tresc): static
    {
        $this->tresc = $tresc;
        return $this;
    }

    public function getAutor(): ?User
    {
        return $this->autor;
    }

    public function setAutor(?User $autor): static
    {
        $this->autor = $autor;
        return $this;
    }

    public function getDataWyslania(): ?\DateTimeInterface
    {
        return $this->dataWyslania;
    }

    public function setDataWyslania(?\DateTimeInterface $dataWyslania): static
    {
        $this->dataWyslania = $dataWyslania;
        return $this;
    }

    public function getLiczbaOdbiorcow(): int
    {
        return $this->liczbaOdbiorcow;
    }

    public function setLiczbaOdbiorcow(int $liczbaOdbiorcow): static
    {
        $this->liczbaOdbiorcow = $liczbaOdbiorcow;
        return $this;
    }

    public function getLiczbaPrzeczytanych(): int
    {
        return $this->liczbaPrzeczytanych;
    }

    public function setLiczbaPrzeczytanych(int $liczbaPrzeczytanych): static
    {
        $this->liczbaPrzeczytanych = $liczbaPrzeczytanych;
        return $this;
    }

    public function getLiczbaOdpowiedzi(): int
    {
        return $this->liczbaOdpowiedzi;
    }

    public function setLiczbaOdpowiedzi(int $liczbaOdpowiedzi): static
    {
        $this->liczbaOdpowiedzi = $liczbaOdpowiedzi;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getDataUtworzenia(): ?\DateTimeInterface
    {
        return $this->dataUtworzenia;
    }

    public function setDataUtworzenia(\DateTimeInterface $dataUtworzenia): static
    {
        $this->dataUtworzenia = $dataUtworzenia;
        return $this;
    }

    /**
     * @return Collection<int, PrzekazOdbiorca>
     */
    public function getOdbiorcy(): Collection
    {
        return $this->odbiorcy;
    }

    public function addOdbiorca(PrzekazOdbiorca $odbiorca): static
    {
        if (!$this->odbiorcy->contains($odbiorca)) {
            $this->odbiorcy->add($odbiorca);
            $odbiorca->setPrzekazMedialny($this);
        }

        return $this;
    }

    public function removeOdbiorca(PrzekazOdbiorca $odbiorca): static
    {
        if ($this->odbiorcy->removeElement($odbiorca)) {
            if ($odbiorca->getPrzekazMedialny() === $this) {
                $odbiorca->setPrzekazMedialny(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PrzekazOdpowiedz>
     */
    public function getOdpowiedzi(): Collection
    {
        return $this->odpowiedzi;
    }

    public function addOdpowiedz(PrzekazOdpowiedz $odpowiedz): static
    {
        if (!$this->odpowiedzi->contains($odpowiedz)) {
            $this->odpowiedzi->add($odpowiedz);
            $odpowiedz->setPrzekazMedialny($this);
        }

        return $this;
    }

    public function removeOdpowiedz(PrzekazOdpowiedz $odpowiedz): static
    {
        if ($this->odpowiedzi->removeElement($odpowiedz)) {
            if ($odpowiedz->getPrzekazMedialny() === $this) {
                $odpowiedz->setPrzekazMedialny(null);
            }
        }

        return $this;
    }

    public function getProcentPrzeczytanych(): float
    {
        if ($this->liczbaOdbiorcow === 0) {
            return 0;
        }
        return round(($this->liczbaPrzeczytanych / $this->liczbaOdbiorcow) * 100, 2);
    }

    public function getProcentOdpowiedzi(): float
    {
        if ($this->liczbaOdbiorcow === 0) {
            return 0;
        }
        return round(($this->liczbaOdpowiedzi / $this->liczbaOdbiorcow) * 100, 2);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isSending(): bool
    {
        return $this->status === self::STATUS_SENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Calculate remaining caption space
     */
    public function getRemainingCaptionLength(): int
    {
        $footer = "Jeśli udostępniłeś ten przekaz, wyślij nam link do swojego posta!";
        $prefix = "📢 ";
        $htmlTags = 10; // <b></b> + newlines margin

        $used = strlen($this->tytul ?? '') + strlen($this->tresc ?? '') + strlen($footer) + strlen($prefix) + $htmlTags;
        return max(0, self::MAX_CAPTION_LENGTH - $used);
    }

    /**
     * Get all allowed MIME types
     */
    public static function getAllowedMimeTypes(): array
    {
        return array_merge(self::ALLOWED_IMAGE_TYPES, self::ALLOWED_VIDEO_TYPES);
    }

    public function getMediaFilePath(): ?string
    {
        return $this->mediaFilePath;
    }

    public function setMediaFilePath(?string $mediaFilePath): static
    {
        $this->mediaFilePath = $mediaFilePath;
        return $this;
    }

    public function getMediaType(): ?string
    {
        return $this->mediaType;
    }

    public function setMediaType(?string $mediaType): static
    {
        $this->mediaType = $mediaType;
        return $this;
    }

    public function hasMedia(): bool
    {
        return $this->mediaFilePath !== null;
    }
}
