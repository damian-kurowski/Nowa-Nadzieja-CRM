<?php

namespace App\Entity;

use App\Repository\ProtokolRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProtokolRepository::class)]
class Protokol
{
    public const STATUS_DRAFT = 'draft';           // Wersja robocza
    public const STATUS_PENDING = 'pending';       // Oczekuje na podpisy
    public const STATUS_SIGNED = 'signed';         // Podpisany
    public const STATUS_APPROVED = 'approved';     // Zatwierdzony
    public const STATUS_ARCHIVED = 'archived';     // Zarchiwizowany

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $tytul;

    #[ORM\Column(length: 50)]
    private string $numerProtokolu;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: Types::TEXT)]
    private string $tresc;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $uchwaly = null; // Lista uchwał podjętych na zebraniu

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $obecni = null; // Lista obecnych członków

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $nieobecni = null; // Lista nieobecnych członków

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataZebrania;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataUtworzenia;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZatwierdzenia = null;

    #[ORM\ManyToOne(targetEntity: ZebranieOkregu::class, inversedBy: 'protokoly')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ZebranieOkregu $zebranieOkregu = null;

    #[ORM\ManyToOne(targetEntity: ZebranieOddzialu::class, inversedBy: 'protokoly')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ZebranieOddzialu $zebranieOddzialu = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $protokolant;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $przewodniczacy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $utworzonePrzez;

    #[ORM\ManyToOne(targetEntity: Okreg::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Okreg $okreg = null;

    #[ORM\ManyToOne(targetEntity: Oddzial::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Oddzial $oddzial = null;

    /**
     * @var Collection<int, PodpisProtokolu>
     */
    #[ORM\OneToMany(mappedBy: 'protokol', targetEntity: PodpisProtokolu::class, cascade: ['persist', 'remove'])]
    private Collection $podpisy;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $uwagi = null;

    public function __construct()
    {
        $this->podpisy = new ArrayCollection();
        $this->dataUtworzenia = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTytul(): ?string
    {
        return $this->tytul;
    }

    public function setTytul(string $tytul): self
    {
        $this->tytul = $tytul;
        return $this;
    }

    public function getNumerProtokolu(): ?string
    {
        return $this->numerProtokolu;
    }

    public function setNumerProtokolu(string $numerProtokolu): self
    {
        $this->numerProtokolu = $numerProtokolu;
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

    public function getTresc(): ?string
    {
        return $this->tresc;
    }

    public function setTresc(string $tresc): self
    {
        $this->tresc = $tresc;
        return $this;
    }

    public function getUchwaly(): ?array
    {
        return $this->uchwaly;
    }

    public function setUchwaly(?array $uchwaly): self
    {
        $this->uchwaly = $uchwaly;
        return $this;
    }

    public function getObecni(): ?array
    {
        return $this->obecni;
    }

    public function setObecni(?array $obecni): self
    {
        $this->obecni = $obecni;
        return $this;
    }

    public function getNieobecni(): ?array
    {
        return $this->nieobecni;
    }

    public function setNieobecni(?array $nieobecni): self
    {
        $this->nieobecni = $nieobecni;
        return $this;
    }

    public function getDataZebrania(): ?\DateTimeInterface
    {
        return $this->dataZebrania;
    }

    public function setDataZebrania(\DateTimeInterface $dataZebrania): self
    {
        $this->dataZebrania = $dataZebrania;
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

    public function getDataZatwierdzenia(): ?\DateTimeInterface
    {
        return $this->dataZatwierdzenia;
    }

    public function setDataZatwierdzenia(?\DateTimeInterface $dataZatwierdzenia): self
    {
        $this->dataZatwierdzenia = $dataZatwierdzenia;
        return $this;
    }

    public function getZebranieOkregu(): ?ZebranieOkregu
    {
        return $this->zebranieOkregu;
    }

    public function setZebranieOkregu(?ZebranieOkregu $zebranieOkregu): self
    {
        $this->zebranieOkregu = $zebranieOkregu;
        return $this;
    }

    public function getZebranieOddzialu(): ?ZebranieOddzialu
    {
        return $this->zebranieOddzialu;
    }

    public function setZebranieOddzialu(?ZebranieOddzialu $zebranieOddzialu): self
    {
        $this->zebranieOddzialu = $zebranieOddzialu;
        return $this;
    }

    public function getProtokolant(): ?User
    {
        return $this->protokolant;
    }

    public function setProtokolant(User $protokolant): self
    {
        $this->protokolant = $protokolant;
        return $this;
    }

    public function getPrzewodniczacy(): ?User
    {
        return $this->przewodniczacy;
    }

    public function setPrzewodniczacy(User $przewodniczacy): self
    {
        $this->przewodniczacy = $przewodniczacy;
        return $this;
    }

    public function getUtworzonePrzez(): ?User
    {
        return $this->utworzonePrzez;
    }

    public function setUtworzonePrzez(User $utworzonePrzez): self
    {
        $this->utworzonePrzez = $utworzonePrzez;
        return $this;
    }

    public function getOkreg(): ?Okreg
    {
        return $this->okreg;
    }

    public function setOkreg(?Okreg $okreg): self
    {
        $this->okreg = $okreg;
        return $this;
    }

    public function getOddzial(): ?Oddzial
    {
        return $this->oddzial;
    }

    public function setOddzial(?Oddzial $oddzial): self
    {
        $this->oddzial = $oddzial;
        return $this;
    }

    /**
     * @return Collection<int, PodpisProtokolu>
     */
    public function getPodpisy(): Collection
    {
        return $this->podpisy;
    }

    public function addPodpis(PodpisProtokolu $podpis): self
    {
        if (!$this->podpisy->contains($podpis)) {
            $this->podpisy->add($podpis);
            $podpis->setProtokol($this);
        }

        return $this;
    }

    public function removePodpis(PodpisProtokolu $podpis): self
    {
        if ($this->podpisy->removeElement($podpis)) {
            if ($podpis->getProtokol() === $this) {
                $podpis->setProtokol(null);
            }
        }

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

    /**
     * Sprawdza czy wszystkie wymagane podpisy zostały złożone
     */
    public function czyPodpisanyPrzezWszystkich(): bool
    {
        foreach ($this->podpisy as $podpis) {
            if ($podpis->getStatus() !== PodpisProtokolu::STATUS_PODPISANY) {
                return false;
            }
        }

        return $this->podpisy->count() > 0;
    }

    /**
     * Generuje numer protokołu
     */
    public function generateNumerProtokolu(): void
    {
        $prefix = 'PROT';

        if ($this->zebranieOkregu) {
            $prefix .= '/OK/' . $this->zebranieOkregu->getOkreg()->getId();
        } elseif ($this->zebranieOddzialu) {
            $prefix .= '/OD/' . $this->zebranieOddzialu->getOddzial()->getId();
        }

        $date = $this->dataZebrania->format('Y/m');
        $this->numerProtokolu = $prefix . '/' . $date . '/' . uniqid();
    }
}
