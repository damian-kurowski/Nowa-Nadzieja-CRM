<?php

namespace App\Entity;

use App\Repository\PlatnoscRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlatnoscRepository::class)]
#[ORM\Index(name: 'idx_platnosc_darczyca', columns: ['darczyca_id'])]
#[ORM\Index(name: 'idx_platnosc_status', columns: ['status_platnosci'])]
#[ORM\Index(name: 'idx_platnosc_data_ksiegowania', columns: ['data_ksiegowania'])]
#[ORM\Index(name: 'idx_platnosc_data_rejestracji', columns: ['data_rejestracji'])]
#[ORM\Index(name: 'idx_platnosc_darczyca_status', columns: ['darczyca_id', 'status_platnosci'])]
class Platnosc
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'platnosci')]
    #[ORM\JoinColumn(nullable: false)]
    private User $darczyca;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataIGodzina;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $kwota;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numerPlatnosci = null;

    #[ORM\Column(length: 50)]
    private string $typWplaty; // 'subskrypcja', 'jednorazowa'

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $opisWplaty = null;

    #[ORM\Column(length: 50)]
    private string $statusPlatnosci; // 'oczekujaca', 'potwierdzona', 'anulowana'

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataRejestracji;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataKsiegowania = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $tytulOperacji = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $peselZTytulu = null;

    #[ORM\ManyToOne(targetEntity: ImportPlatnosci::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ImportPlatnosci $importPlatnosci = null;

    public function __construct()
    {
        $this->dataRejestracji = new \DateTime();
        $this->statusPlatnosci = 'oczekujaca';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDarczyca(): User
    {
        return $this->darczyca;
    }

    public function setDarczyca(User $darczyca): self
    {
        $this->darczyca = $darczyca;

        return $this;
    }

    public function getDataIGodzina(): \DateTimeInterface
    {
        return $this->dataIGodzina;
    }

    public function setDataIGodzina(\DateTimeInterface $dataIGodzina): self
    {
        $this->dataIGodzina = $dataIGodzina;

        return $this;
    }

    public function getKwota(): string
    {
        return $this->kwota;
    }

    public function setKwota(string $kwota): self
    {
        $this->kwota = $kwota;

        return $this;
    }

    public function getNumerPlatnosci(): ?string
    {
        return $this->numerPlatnosci;
    }

    public function setNumerPlatnosci(?string $numerPlatnosci): self
    {
        $this->numerPlatnosci = $numerPlatnosci;

        return $this;
    }

    public function getTypWplaty(): string
    {
        return $this->typWplaty;
    }

    public function setTypWplaty(string $typWplaty): self
    {
        $this->typWplaty = $typWplaty;

        return $this;
    }

    public function getOpisWplaty(): ?string
    {
        return $this->opisWplaty;
    }

    public function setOpisWplaty(?string $opisWplaty): self
    {
        $this->opisWplaty = $opisWplaty;

        return $this;
    }

    public function getStatusPlatnosci(): string
    {
        return $this->statusPlatnosci;
    }

    public function setStatusPlatnosci(string $statusPlatnosci): self
    {
        $this->statusPlatnosci = $statusPlatnosci;

        return $this;
    }

    public function getDataRejestracji(): \DateTimeInterface
    {
        return $this->dataRejestracji;
    }

    public function setDataRejestracji(\DateTimeInterface $dataRejestracji): self
    {
        $this->dataRejestracji = $dataRejestracji;

        return $this;
    }

    public function getDataKsiegowania(): ?\DateTimeInterface
    {
        return $this->dataKsiegowania;
    }

    public function setDataKsiegowania(?\DateTimeInterface $dataKsiegowania): self
    {
        $this->dataKsiegowania = $dataKsiegowania;
        return $this;
    }

    public function getTytulOperacji(): ?string
    {
        return $this->tytulOperacji;
    }

    public function setTytulOperacji(?string $tytulOperacji): self
    {
        $this->tytulOperacji = $tytulOperacji;
        return $this;
    }

    public function getPeselZTytulu(): ?string
    {
        return $this->peselZTytulu;
    }

    public function setPeselZTytulu(?string $peselZTytulu): self
    {
        $this->peselZTytulu = $peselZTytulu;
        return $this;
    }

    public function getImportPlatnosci(): ?ImportPlatnosci
    {
        return $this->importPlatnosci;
    }

    public function setImportPlatnosci(?ImportPlatnosci $importPlatnosci): self
    {
        $this->importPlatnosci = $importPlatnosci;
        return $this;
    }
}
