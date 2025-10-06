<?php

namespace App\Entity;

use App\Repository\PrzekazOdpowiedzRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrzekazOdpowiedzRepository::class)]
#[ORM\Table(name: 'przekaz_odpowiedz')]
#[ORM\Index(columns: ['typ'], name: 'idx_odpowiedz_typ')]
#[ORM\Index(columns: ['zweryfikowany'], name: 'idx_odpowiedz_zweryfikowany')]
#[ORM\Index(columns: ['data_dodania'], name: 'idx_odpowiedz_data_dodania')]
class PrzekazOdpowiedz
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PrzekazMedialny::class, inversedBy: 'odpowiedzi')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PrzekazMedialny $przekaz = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $odbiorca = null;

    #[ORM\Column(length: 20)]
    private string $typ = 'inne'; // facebook, twitter_x, instagram, tiktok, inne

    #[ORM\Column(length: 500)]
    private ?string $linkUrl = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dataDodania = null;

    #[ORM\Column]
    private bool $zweryfikowany = false;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dataWeryfikacji = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $zweryfikowalPrzez = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $uwagi = null;

    public function __construct()
    {
        $this->dataDodania = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrzekazMedialny(): ?PrzekazMedialny
    {
        return $this->przekaz;
    }

    public function setPrzekazMedialny(?PrzekazMedialny $przekaz): static
    {
        $this->przekaz = $przekaz;
        return $this;
    }

    public function getOdbiorca(): ?User
    {
        return $this->odbiorca;
    }

    public function setOdbiorca(?User $odbiorca): static
    {
        $this->odbiorca = $odbiorca;
        return $this;
    }

    public function getTyp(): string
    {
        return $this->typ;
    }

    public function setTyp(string $typ): static
    {
        $this->typ = $typ;
        return $this;
    }

    public function getLinkUrl(): ?string
    {
        return $this->linkUrl;
    }

    public function setLinkUrl(string $linkUrl): static
    {
        $this->linkUrl = $linkUrl;
        return $this;
    }

    public function getDataDodania(): ?\DateTimeInterface
    {
        return $this->dataDodania;
    }

    public function setDataDodania(\DateTimeInterface $dataDodania): static
    {
        $this->dataDodania = $dataDodania;
        return $this;
    }

    public function isZweryfikowany(): bool
    {
        return $this->zweryfikowany;
    }

    public function setZweryfikowany(bool $zweryfikowany): static
    {
        $this->zweryfikowany = $zweryfikowany;
        return $this;
    }

    public function getDataWeryfikacji(): ?\DateTimeInterface
    {
        return $this->dataWeryfikacji;
    }

    public function setDataWeryfikacji(?\DateTimeInterface $dataWeryfikacji): static
    {
        $this->dataWeryfikacji = $dataWeryfikacji;
        return $this;
    }

    public function getZweryfikowalPrzez(): ?User
    {
        return $this->zweryfikowalPrzez;
    }

    public function setZweryfikowalPrzez(?User $zweryfikowalPrzez): static
    {
        $this->zweryfikowalPrzez = $zweryfikowalPrzez;
        return $this;
    }

    public function getUwagi(): ?string
    {
        return $this->uwagi;
    }

    public function setUwagi(?string $uwagi): static
    {
        $this->uwagi = $uwagi;
        return $this;
    }

    /**
     * Automatyczna detekcja typu linku
     */
    public function detectTypFromUrl(): void
    {
        $url = strtolower($this->linkUrl);

        if (str_contains($url, 'facebook.com') || str_contains($url, 'fb.com')) {
            $this->typ = 'facebook';
        } elseif (str_contains($url, 'twitter.com') || str_contains($url, 'x.com')) {
            $this->typ = 'twitter_x';
        } elseif (str_contains($url, 'instagram.com')) {
            $this->typ = 'instagram';
        } elseif (str_contains($url, 'tiktok.com')) {
            $this->typ = 'tiktok';
        } else {
            $this->typ = 'inne';
        }
    }

    public function getTypNazwa(): string
    {
        return match ($this->typ) {
            'facebook' => 'Facebook',
            'twitter_x' => 'X (Twitter)',
            'instagram' => 'Instagram',
            'tiktok' => 'TikTok',
            default => 'Inne',
        };
    }

    public function getTypIcon(): string
    {
        return match ($this->typ) {
            'facebook' => 'fab fa-facebook',
            'twitter_x' => 'fab fa-x-twitter',
            'instagram' => 'fab fa-instagram',
            'tiktok' => 'fab fa-tiktok',
            default => 'fas fa-link',
        };
    }
}
