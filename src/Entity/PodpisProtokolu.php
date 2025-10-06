<?php

namespace App\Entity;

use App\Repository\PodpisProtokolRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PodpisProtokolRepository::class)]
class PodpisProtokolu
{
    public const STATUS_OCZEKUJE = 'oczekuje';
    public const STATUS_PODPISANY = 'podpisany';
    public const STATUS_ODRZUCONY = 'odrzucony';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Protokol::class, inversedBy: 'podpisy')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Protokol $protokol = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $podpisujacy;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_OCZEKUJE;

    #[ORM\Column]
    private int $kolejnosc;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataUtworzenia;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisania = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $podpisElektroniczny = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $uwagi = null;

    public function __construct()
    {
        $this->dataUtworzenia = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProtokol(): ?Protokol
    {
        return $this->protokol;
    }

    public function setProtokol(?Protokol $protokol): self
    {
        $this->protokol = $protokol;
        return $this;
    }

    public function getPodpisujacy(): ?User
    {
        return $this->podpisujacy;
    }

    public function setPodpisujacy(User $podpisujacy): self
    {
        $this->podpisujacy = $podpisujacy;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getKolejnosc(): ?int
    {
        return $this->kolejnosc;
    }

    public function setKolejnosc(int $kolejnosc): self
    {
        $this->kolejnosc = $kolejnosc;
        return $this;
    }

    public function getDataUtworzenia(): ?\DateTimeInterface
    {
        return $this->dataUtworzenia;
    }

    public function setDataUtworzenia(\DateTimeInterface $dataUtworzenia): self
    {
        $this->dataUtworzenia = $dataUtworzenia;
        return $this;
    }

    public function getDataPodpisania(): ?\DateTimeInterface
    {
        return $this->dataPodpisania;
    }

    public function setDataPodpisania(?\DateTimeInterface $dataPodpisania): self
    {
        $this->dataPodpisania = $dataPodpisania;
        return $this;
    }

    public function getPodpisElektroniczny(): ?string
    {
        return $this->podpisElektroniczny;
    }

    public function setPodpisElektroniczny(?string $podpisElektroniczny): self
    {
        $this->podpisElektroniczny = $podpisElektroniczny;
        return $this;
    }

    public function getUwagi(): ?string
    {
        return $this->uwagi;
    }

    public function setUwagi(?string $uwagi): self
    {
        $this->uwagi = $uwagi;
        return $this;
    }
}
