<?php

namespace App\Entity;

use App\Repository\FakturaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FakturaRepository::class)]
#[ORM\Index(name: 'idx_faktura_status', columns: ['status'])]
#[ORM\Index(name: 'idx_faktura_pilnosc', columns: ['pilnosc'])]
#[ORM\Index(name: 'idx_faktura_kategoria', columns: ['kategoria'])]
#[ORM\Index(name: 'idx_faktura_skarbnik', columns: ['skarbnik_id'])]
#[ORM\Index(name: 'idx_faktura_okreg', columns: ['okreg_id'])]
#[ORM\Index(name: 'idx_faktura_data_platnosci', columns: ['data_platnosci'])]
#[ORM\Index(name: 'idx_faktura_data_utworzenia', columns: ['data_utworzenia'])]
class Faktura
{
    public const STATUS_WPROWADZONE = 'wprowadzone';
    public const STATUS_ZAAKCEPTOWANE = 'zaakceptowane';
    public const STATUS_ODRZUCONE = 'odrzucone';
    public const STATUS_ZREALIZOWANE = 'zrealizowane';

    public const PILNOSC_NORMALNA = 'normalna';
    public const PILNOSC_PILNA = 'pilna';

    public const KATEGORIA_OBSLUGA_BIURA = 'obsluga_biura';
    public const KATEGORIA_WYDARZENIA = 'wydarzenia';
    public const KATEGORIA_KAMPANIA = 'kampania';
    public const KATEGORIA_INNE = 'inne';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $numerFaktury;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $kwota;

    #[ORM\Column(length: 34)]
    private string $numerKonta;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataPlatnosci;

    #[ORM\Column(type: Types::TEXT)]
    private string $celPlatnosci;

    #[ORM\Column(length: 50)]
    private string $kategoria;

    #[ORM\Column(length: 20)]
    private string $pilnosc;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $uzasadnienieOdrzucenia = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataUtworzenia;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataAkceptacji = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataRealizacji = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $skarbnik;

    #[ORM\ManyToOne(targetEntity: Okreg::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Okreg $okreg;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $skarbnikPartiiAkceptujacy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nazwaDostaway = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresDostaway = null;

    public function __construct()
    {
        $this->dataUtworzenia = new \DateTime();
        $this->status = self::STATUS_WPROWADZONE;
        $this->pilnosc = self::PILNOSC_NORMALNA;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumerFaktury(): string
    {
        return $this->numerFaktury;
    }

    public function setNumerFaktury(string $numerFaktury): self
    {
        $this->numerFaktury = $numerFaktury;
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

    public function getNumerKonta(): string
    {
        return $this->numerKonta;
    }

    public function setNumerKonta(string $numerKonta): self
    {
        $this->numerKonta = $numerKonta;
        return $this;
    }

    public function getDataPlatnosci(): \DateTimeInterface
    {
        return $this->dataPlatnosci;
    }

    public function setDataPlatnosci(\DateTimeInterface $dataPlatnosci): self
    {
        $this->dataPlatnosci = $dataPlatnosci;
        return $this;
    }

    public function getCelPlatnosci(): string
    {
        return $this->celPlatnosci;
    }

    public function setCelPlatnosci(string $celPlatnosci): self
    {
        $this->celPlatnosci = $celPlatnosci;
        return $this;
    }

    public function getKategoria(): string
    {
        return $this->kategoria;
    }

    public function setKategoria(string $kategoria): self
    {
        $this->kategoria = $kategoria;
        return $this;
    }

    public function getPilnosc(): string
    {
        return $this->pilnosc;
    }

    public function setPilnosc(string $pilnosc): self
    {
        $this->pilnosc = $pilnosc;
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

    public function getUzasadnienieOdrzucenia(): ?string
    {
        return $this->uzasadnienieOdrzucenia;
    }

    public function setUzasadnienieOdrzucenia(?string $uzasadnienieOdrzucenia): self
    {
        $this->uzasadnienieOdrzucenia = $uzasadnienieOdrzucenia;
        return $this;
    }

    public function getDataUtworzenia(): \DateTimeInterface
    {
        return $this->dataUtworzenia;
    }

    public function setDataUtworzenia(\DateTimeInterface $dataUtworzenia): self
    {
        $this->dataUtworzenia = $dataUtworzenia;
        return $this;
    }

    public function getDataAkceptacji(): ?\DateTimeInterface
    {
        return $this->dataAkceptacji;
    }

    public function setDataAkceptacji(?\DateTimeInterface $dataAkceptacji): self
    {
        $this->dataAkceptacji = $dataAkceptacji;
        return $this;
    }

    public function getDataRealizacji(): ?\DateTimeInterface
    {
        return $this->dataRealizacji;
    }

    public function setDataRealizacji(?\DateTimeInterface $dataRealizacji): self
    {
        $this->dataRealizacji = $dataRealizacji;
        return $this;
    }

    public function getSkarbnik(): User
    {
        return $this->skarbnik;
    }

    public function setSkarbnik(User $skarbnik): self
    {
        $this->skarbnik = $skarbnik;
        return $this;
    }

    public function getOkreg(): Okreg
    {
        return $this->okreg;
    }

    public function setOkreg(Okreg $okreg): self
    {
        $this->okreg = $okreg;
        return $this;
    }

    public function getSkarbnikPartiiAkceptujacy(): ?User
    {
        return $this->skarbnikPartiiAkceptujacy;
    }

    public function setSkarbnikPartiiAkceptujacy(?User $skarbnikPartiiAkceptujacy): self
    {
        $this->skarbnikPartiiAkceptujacy = $skarbnikPartiiAkceptujacy;
        return $this;
    }

    public function getNazwaDostaway(): ?string
    {
        return $this->nazwaDostaway;
    }

    public function setNazwaDostaway(?string $nazwaDostaway): self
    {
        $this->nazwaDostaway = $nazwaDostaway;
        return $this;
    }

    public function getAdresDostaway(): ?string
    {
        return $this->adresDostaway;
    }

    public function setAdresDostaway(?string $adresDostaway): self
    {
        $this->adresDostaway = $adresDostaway;
        return $this;
    }

    // Helper methods
    public function isPilna(): bool
    {
        return $this->pilnosc === self::PILNOSC_PILNA;
    }

    public function isWprowadzone(): bool
    {
        return $this->status === self::STATUS_WPROWADZONE;
    }

    public function isZaakceptowane(): bool
    {
        return $this->status === self::STATUS_ZAAKCEPTOWANE;
    }

    public function isOdrzucone(): bool
    {
        return $this->status === self::STATUS_ODRZUCONE;
    }

    public function isZrealizowane(): bool
    {
        return $this->status === self::STATUS_ZREALIZOWANE;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_WPROWADZONE => 'Wprowadzone',
            self::STATUS_ZAAKCEPTOWANE => 'Zaakceptowane do realizacji',
            self::STATUS_ODRZUCONE => 'Odrzucone',
            self::STATUS_ZREALIZOWANE => 'Zrealizowane',
            default => 'Nieznany status'
        };
    }

    public function getKategoriaLabel(): string
    {
        return match ($this->kategoria) {
            self::KATEGORIA_OBSLUGA_BIURA => 'Obsługa biura',
            self::KATEGORIA_WYDARZENIA => 'Wydarzenia',
            self::KATEGORIA_KAMPANIA => 'Kampania',
            self::KATEGORIA_INNE => 'Inne',
            default => 'Nieznana kategoria'
        };
    }

    public function getPilnoscLabel(): string
    {
        return match ($this->pilnosc) {
            self::PILNOSC_NORMALNA => 'Normalna',
            self::PILNOSC_PILNA => 'Pilna',
            default => 'Nieznany priorytet'
        };
    }

    public static function getStatusChoices(): array
    {
        return [
            'Wprowadzone' => self::STATUS_WPROWADZONE,
            'Zaakceptowane do realizacji' => self::STATUS_ZAAKCEPTOWANE,
            'Odrzucone' => self::STATUS_ODRZUCONE,
            'Zrealizowane' => self::STATUS_ZREALIZOWANE,
        ];
    }

    public static function getKategoriaChoices(): array
    {
        return [
            'Obsługa biura' => self::KATEGORIA_OBSLUGA_BIURA,
            'Wydarzenia' => self::KATEGORIA_WYDARZENIA,
            'Kampania' => self::KATEGORIA_KAMPANIA,
            'Inne' => self::KATEGORIA_INNE,
        ];
    }

    public static function getPilnoscChoices(): array
    {
        return [
            'Normalna' => self::PILNOSC_NORMALNA,
            'Pilna' => self::PILNOSC_PILNA,
        ];
    }
}