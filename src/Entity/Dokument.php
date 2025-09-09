<?php

namespace App\Entity;

use App\Repository\DokumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DokumentRepository::class)]
class Dokument
{
    public const STATUS_PROJEKT = 'draft';
    public const STATUS_DRAFT = 'draft';  // Alias for compatibility
    public const STATUS_CZEKA_NA_PODPIS = 'pending';
    public const STATUS_OCZEKUJE_NA_PODPIS = 'awaiting_signature';
    public const STATUS_PODPISANY = 'signed';
    public const STATUS_ANULOWANY = 'rejected';

    // Typy dokumentów przyjęcia członka
    public const TYP_PRZYJECIE_CZLONKA = 'przyjecie_czlonka';  // Generic type for compatibility
    public const TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK = 'przyjecie_czlonka_pelnomocnik';
    public const TYP_PRZYJECIE_CZLONKA_OKREG = 'przyjecie_czlonka_okreg';
    public const TYP_PRZYJECIE_CZLONKA_KRAJOWY = 'przyjecie_czlonka_krajowy';

    // Typy dokumentów powołania i odwołania
    public const TYP_POWOLANIE_PELNOMOCNIK_STRUKTUR = 'powolanie_pelnomocnik_struktur';
    public const TYP_ODWOLANIE_PELNOMOCNIK_STRUKTUR = 'odwolanie_pelnomocnik_struktur';
    public const TYP_POWOLANIE_SEKRETARZ_PARTII = 'powolanie_sekretarz_partii';
    public const TYP_ODWOLANIE_SEKRETARZ_PARTII = 'odwolanie_sekretarz_partii';
    public const TYP_POWOLANIE_SKARBNIK_PARTII = 'powolanie_skarbnik_partii';
    public const TYP_ODWOLANIE_SKARBNIK_PARTII = 'odwolanie_skarbnik_partii';
    public const TYP_POWOLANIE_WICEPREZES_PARTII = 'powolanie_wiceprezes_partii';
    public const TYP_ODWOLANIE_WICEPREZES_PARTII = 'odwolanie_wiceprezes_partii';
    public const TYP_ODWOLANIE_PREZES_OKREGU = 'odwolanie_prezes_okregu';
    public const TYP_POWOLANIE_PO_PREZES_OKREGU = 'powolanie_po_prezes_okregu';
    public const TYP_ODWOLANIE_PO_PREZES_OKREGU = 'odwolanie_po_prezes_okregu';
    public const TYP_POWOLANIE_SEKRETARZ_OKREGU = 'powolanie_sekretarz_okregu';
    public const TYP_ODWOLANIE_SEKRETARZ_OKREGU = 'odwolanie_sekretarz_okregu';
    public const TYP_POWOLANIE_SKARBNIK_OKREGU = 'powolanie_skarbnik_okregu';
    public const TYP_ODWOLANIE_SKARBNIK_OKREGU = 'odwolanie_skarbnik_okregu';
    public const TYP_UTWORZENIE_ODDZIALU = 'utworzenie_oddzialu';

    // Typy dokumentów zebrania członków oddziału
    public const TYP_WYZNACZENIE_OBSERWATORA = 'wyznaczenie_obserwatora_zebrania';
    public const TYP_WYZNACZENIE_PROTOKOLANTA = 'wyznaczenie_protokolanta_zebrania';
    public const TYP_WYZNACZENIE_PROWADZACEGO = 'wyznaczenie_prowadzacego_zebrania';
    public const TYP_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU = 'powolanie_przewodniczacego_oddzialu';
    public const TYP_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU = 'odwolanie_przewodniczacego_oddzialu';
    public const TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO = 'powolanie_zastepcy_przewodniczacego';
    public const TYP_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO = 'odwolanie_zastepcy_przewodniczacego';
    public const TYP_POWOLANIE_SEKRETARZA_ODDZIALU = 'powolanie_sekretarza_oddzialu';
    public const TYP_ODWOLANIE_SEKRETARZA_ODDZIALU = 'odwolanie_sekretarza_oddzialu';

    // Typy dokumentów zebrania członków okręgu
    public const TYP_WYBOR_PREZESA_OKREGU_WALNE = 'wybor_prezesa_okregu_walne';
    public const TYP_WYBOR_WICEPREZESA_OKREGU_WALNE = 'wybor_wiceprezesa_okregu_walne';

    // Nowe typy dokumentów - funkcje tymczasowe
    public const TYP_WYZNACZENIE_OSOBY_TYMCZASOWEJ = 'wyznaczenie_osoby_tymczasowej';

    // Nowe typy dokumentów - poziom regionu
    public const TYP_POWOLANIE_PREZES_REGIONU = 'powolanie_prezes_regionu';
    public const TYP_ODWOLANIE_PREZES_REGIONU = 'odwolanie_prezes_regionu';
    public const TYP_WYBOR_SEKRETARZ_REGIONU = 'wybor_sekretarz_regionu';
    public const TYP_WYBOR_SKARBNIK_REGIONU = 'wybor_skarbnik_regionu';

    // Nowe typy dokumentów - Rada Krajowa
    public const TYP_WYBOR_PRZEWODNICZACY_RADY = 'wybor_przewodniczacy_rady';
    public const TYP_WYBOR_ZASTEPCA_PRZEWODNICZACY_RADY = 'wybor_zastepca_przewodniczacy_rady';
    public const TYP_ODWOLANIE_PRZEWODNICZACY_RADY = 'odwolanie_przewodniczacy_rady';
    public const TYP_ODWOLANIE_ZASTEPCA_PRZEWODNICZACY_RADY = 'odwolanie_zastepca_przewodniczacy_rady';

    // Nowe typy dokumentów - Komisja Rewizyjna
    public const TYP_WYBOR_PRZEWODNICZACY_KOMISJI_REWIZYJNEJ = 'wybor_przewodniczacy_komisji_rewizyjnej';
    public const TYP_WYBOR_WICEPRZEWODNICZACY_KOMISJI_REWIZYJNEJ = 'wybor_wiceprzewodniczacy_komisji_rewizyjnej';
    public const TYP_WYBOR_SEKRETARZ_KOMISJI_REWIZYJNEJ = 'wybor_sekretarz_komisji_rewizyjnej';
    public const TYP_ODWOLANIE_PRZEWODNICZACY_KOMISJI_REWIZYJNEJ = 'odwolanie_przewodniczacy_komisji_rewizyjnej';
    public const TYP_ODWOLANIE_WICEPRZEWODNICZACY_KOMISJI_REWIZYJNEJ = 'odwolanie_wiceprzewodniczacy_komisji_rewizyjnej';
    public const TYP_ODWOLANIE_SEKRETARZ_KOMISJI_REWIZYJNEJ = 'odwolanie_sekretarz_komisji_rewizyjnej';

    // Nowe typy dokumentów - struktury parlamentarne
    public const TYP_POWOLANIE_PRZEWODNICZACY_KLUBU = 'powolanie_przewodniczacy_klubu';
    public const TYP_ODWOLANIE_PRZEWODNICZACY_KLUBU = 'odwolanie_przewodniczacy_klubu';
    public const TYP_WYBOR_PRZEWODNICZACY_DELEGACJI = 'wybor_przewodniczacy_delegacji';
    public const TYP_ODWOLANIE_PRZEWODNICZACY_DELEGACJI = 'odwolanie_przewodniczacy_delegacji';

    // Najnowsze typy dokumentów - członkostwo i rezygnacje
    public const TYP_OSWIADCZENIE_WYSTAPIENIA = 'oswiadczenie_wystapienia';
    public const TYP_UCHWALA_SKRESLENIA_CZLONKA = 'uchwala_skreslenia_czlonka';
    public const TYP_WNIOSEK_ZAWIESZENIA_CZLONKOSTWA = 'wniosek_zawieszenia_czlonkostwa';
    public const TYP_WNIOSEK_ODWIESZENIA_CZLONKOSTWA = 'wniosek_odwieszenia_czlonkostwa';
    public const TYP_POSTANOWIENIE_SADU_PARTYJNEGO = 'postanowienie_sadu_partyjnego';
    public const TYP_REZYGNACJA_Z_FUNKCJI = 'rezygnacja_z_funkcji';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private string $typ;

    #[ORM\Column(length: 100, unique: true)]
    private string $numerDokumentu;

    #[ORM\Column(length: 255)]
    private string $tytul;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tresc = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_PROJEKT;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private \DateTimeInterface $dataWejsciaWZycie;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataUtworzenia;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisania = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $protokolantPodpisal = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $prowadzacyPodpisal = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $tworca;

    #[ORM\ManyToOne(targetEntity: Okreg::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Okreg $okreg = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $kandydat = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $czlonek = null;

    /**
     * @var Collection<int, PodpisDokumentu>
     */
    #[ORM\OneToMany(mappedBy: 'dokument', targetEntity: PodpisDokumentu::class, cascade: ['persist', 'remove'])]
    private Collection $podpisy;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $daneDodatkowe = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $hashDokumentu = null;

    #[ORM\ManyToOne(targetEntity: ZebranieOddzialu::class, inversedBy: 'dokumenty')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ZebranieOddzialu $zebranieOddzialu = null;

    #[ORM\ManyToOne(targetEntity: ZebranieOkregu::class, inversedBy: 'dokumenty')]
    #[ORM\JoinColumn(nullable: true)]
    private ?ZebranieOkregu $zebranieOkregu = null;

    public function __construct()
    {
        $this->podpisy = new ArrayCollection();
        $this->dataUtworzenia = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTyp(): string
    {
        return $this->typ;
    }

    public function setTyp(string $typ): self
    {
        $this->typ = $typ;

        return $this;
    }

    public function getNumerDokumentu(): string
    {
        return $this->numerDokumentu;
    }

    public function setNumerDokumentu(string $numerDokumentu): self
    {
        $this->numerDokumentu = $numerDokumentu;

        return $this;
    }

    public function getTytul(): string
    {
        return $this->tytul;
    }

    public function setTytul(string $tytul): self
    {
        $this->tytul = $tytul;

        return $this;
    }

    public function getTresc(): ?string
    {
        return $this->tresc;
    }

    public function setTresc(?string $tresc): self
    {
        $this->tresc = $tresc;

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

    public function getDataWejsciaWZycie(): \DateTimeInterface
    {
        return $this->dataWejsciaWZycie;
    }

    public function setDataWejsciaWZycie(\DateTimeInterface $dataWejsciaWZycie): self
    {
        $this->dataWejsciaWZycie = $dataWejsciaWZycie;

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

    public function getDataPodpisania(): ?\DateTimeInterface
    {
        return $this->dataPodpisania;
    }

    public function setDataPodpisania(?\DateTimeInterface $dataPodpisania): self
    {
        $this->dataPodpisania = $dataPodpisania;

        return $this;
    }

    public function getTworca(): User
    {
        return $this->tworca;
    }

    public function setTworca(User $tworca): self
    {
        $this->tworca = $tworca;

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

    public function getKandydat(): ?User
    {
        return $this->kandydat;
    }

    public function setKandydat(?User $kandydat): self
    {
        $this->kandydat = $kandydat;

        return $this;
    }

    public function getCzlonek(): ?User
    {
        return $this->czlonek;
    }

    public function setCzlonek(?User $czlonek): self
    {
        $this->czlonek = $czlonek;

        return $this;
    }

    /**
     * @return Collection<int, PodpisDokumentu>
     */
    public function getPodpisy(): Collection
    {
        return $this->podpisy;
    }

    public function addPodpis(PodpisDokumentu $podpis): self
    {
        if (!$this->podpisy->contains($podpis)) {
            $this->podpisy->add($podpis);
            $podpis->setDokument($this);
        }

        return $this;
    }

    public function removePodpis(PodpisDokumentu $podpis): self
    {
        if ($this->podpisy->removeElement($podpis)) {
            if ($podpis->getDokument() === $this) {
                $podpis->setDokument(null);
            }
        }

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    /**
     * @return array<string, mixed>|null
     */
    public function getDaneDodatkowe(): ?array
    {
        return $this->daneDodatkowe;
    }

    /**
     * @param array<string, mixed>|null $daneDodatkowe
     */
    public function setDaneDodatkowe(?array $daneDodatkowe): self
    {
        $this->daneDodatkowe = $daneDodatkowe;

        return $this;
    }

    public function getHashDokumentu(): ?string
    {
        return $this->hashDokumentu;
    }

    public function setHashDokumentu(?string $hashDokumentu): self
    {
        $this->hashDokumentu = $hashDokumentu;

        return $this;
    }

    /**
     * Generuje unikalny numer dokumentu na podstawie daty, okręgu i typu.
     */
    public function generateNumerDokumentu(): void
    {
        $rok = date('Y');
        $miesiac = date('m');
        $dzien = date('d');

        // Format: DOK/2024/12/08/001/WRO/PC (PC = Przyjęcie Członka)
        $prefix = 'DOK';
        $typSkrot = $this->getTypSkrot();
        $okregSkrot = $this->okreg ? $this->okreg->getSkrot() : 'KRA';

        // Numer sekwencyjny będzie ustalony w serwisie
        $this->numerDokumentu = "{$prefix}/{$rok}/{$miesiac}/{$dzien}/000/{$okregSkrot}/{$typSkrot}";
    }

    public function getTypSkrot(): string
    {
        return self::getTypSkrotStatic($this->typ);
    }

    public static function getTypSkrotStatic(string $typ): string
    {
        return match ($typ) {
            self::TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK => 'PCP',
            self::TYP_PRZYJECIE_CZLONKA_OKREG => 'PCO',
            self::TYP_PRZYJECIE_CZLONKA_KRAJOWY => 'PCK',
            self::TYP_POWOLANIE_PELNOMOCNIK_STRUKTUR => 'PPS',
            self::TYP_ODWOLANIE_PELNOMOCNIK_STRUKTUR => 'OPS',
            self::TYP_POWOLANIE_SEKRETARZ_PARTII => 'PSP',
            self::TYP_ODWOLANIE_SEKRETARZ_PARTII => 'OSP',
            self::TYP_POWOLANIE_SKARBNIK_PARTII => 'PSKP',
            self::TYP_ODWOLANIE_SKARBNIK_PARTII => 'OSKP',
            self::TYP_POWOLANIE_WICEPREZES_PARTII => 'PWP',
            self::TYP_ODWOLANIE_WICEPREZES_PARTII => 'OWP',
            self::TYP_ODWOLANIE_PREZES_OKREGU => 'OPO',
            self::TYP_POWOLANIE_PO_PREZES_OKREGU => 'PPOPO',
            self::TYP_ODWOLANIE_PO_PREZES_OKREGU => 'OPOPO',
            self::TYP_POWOLANIE_SEKRETARZ_OKREGU => 'PSO',
            self::TYP_ODWOLANIE_SEKRETARZ_OKREGU => 'OSO',
            self::TYP_POWOLANIE_SKARBNIK_OKREGU => 'PSKO',
            self::TYP_ODWOLANIE_SKARBNIK_OKREGU => 'OSKO',
            self::TYP_UTWORZENIE_ODDZIALU => 'UO',
            self::TYP_WYZNACZENIE_OBSERWATORA => 'WOZ',
            self::TYP_WYZNACZENIE_PROTOKOLANTA => 'WPZ',
            self::TYP_WYZNACZENIE_PROWADZACEGO => 'WPRZ',
            self::TYP_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU => 'PPO',
            self::TYP_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU => 'OPO',
            self::TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => 'PZP',
            self::TYP_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => 'OZP',
            self::TYP_POWOLANIE_SEKRETARZA_ODDZIALU => 'PSOD',
            self::TYP_ODWOLANIE_SEKRETARZA_ODDZIALU => 'OSOD',
            self::TYP_WYBOR_PREZESA_OKREGU_WALNE => 'WPOW',
            self::TYP_WYBOR_WICEPREZESA_OKREGU_WALNE => 'WWOW',
            default => 'XX',
        };
    }

    /**
     * Sprawdza czy dokument jest w pełni podpisany.
     */
    public function isFullySigned(): bool
    {
        if ($this->podpisy->isEmpty()) {
            return false;
        }

        foreach ($this->podpisy as $podpis) {
            if (!$podpis->isSigned()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sprawdza czy użytkownik może podpisać dokument.
     */
    public function canUserSign(User $user): bool
    {
        // Anulowane dokumenty nie mogą być podpisane
        if (self::STATUS_ANULOWANY === $this->status) {
            return false;
        }

        // Już podpisane dokumenty nie mogą być ponownie podpisane
        if (self::STATUS_PODPISANY === $this->status) {
            return false;
        }

        foreach ($this->podpisy as $podpis) {
            if ($podpis->getPodpisujacy() === $user && !$podpis->isSigned()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Zwraca podpis użytkownika jeśli istnieje.
     */
    public function getUserSignature(User $user): ?PodpisDokumentu
    {
        foreach ($this->podpisy as $podpis) {
            if ($podpis->getPodpisujacy() === $user) {
                return $podpis;
            }
        }

        return null;
    }

    /**
     * Zwraca kolor badge'a na podstawie statusu.
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PROJEKT => 'bg-secondary',
            self::STATUS_CZEKA_NA_PODPIS => 'bg-warning',
            self::STATUS_PODPISANY => 'bg-success',
            self::STATUS_ANULOWANY => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Zwraca nazwę statusu dla wyświetlenia.
     */
    public function getStatusDisplayName(): string
    {
        return match ($this->status) {
            self::STATUS_PROJEKT => 'Projekt',
            self::STATUS_CZEKA_NA_PODPIS => 'Czeka na podpis',
            self::STATUS_PODPISANY => 'Podpisany',
            self::STATUS_ANULOWANY => 'Anulowany',
            default => 'Nieznany',
        };
    }

    /**
     * Zwraca nazwę typu dla wyświetlenia.
     */
    public function getTypDisplayName(): string
    {
        return match ($this->typ) {
            self::TYP_PRZYJECIE_CZLONKA_PELNOMOCNIK => 'Przyjęcie członka przez Okręgowego Pełnomocnika ds. przyjmowania nowych członków',
            self::TYP_PRZYJECIE_CZLONKA_OKREG => 'Przyjęcie członka przez zarząd okręgu',
            self::TYP_PRZYJECIE_CZLONKA_KRAJOWY => 'Przyjęcie członka przez zarząd krajowy',
            self::TYP_POWOLANIE_PELNOMOCNIK_STRUKTUR => 'Powołanie Pełnomocnika ds. Struktur przez Prezesa Partii',
            self::TYP_ODWOLANIE_PELNOMOCNIK_STRUKTUR => 'Odwołanie Pełnomocnika ds. Struktur przez Prezesa Partii',
            self::TYP_POWOLANIE_SEKRETARZ_PARTII => 'Powołanie Sekretarza Partii przez Prezesa Partii',
            self::TYP_ODWOLANIE_SEKRETARZ_PARTII => 'Odwołanie Sekretarza Partii przez Prezesa Partii',
            self::TYP_POWOLANIE_SKARBNIK_PARTII => 'Powołanie Skarbnika Partii przez Prezesa Partii',
            self::TYP_ODWOLANIE_SKARBNIK_PARTII => 'Odwołanie Skarbnika Partii przez Prezesa Partii',
            self::TYP_POWOLANIE_WICEPREZES_PARTII => 'Powołanie Wiceprezesa Partii przez Prezesa Partii',
            self::TYP_ODWOLANIE_WICEPREZES_PARTII => 'Odwołanie Wiceprezesa Partii przez Prezesa Partii',
            self::TYP_ODWOLANIE_PREZES_OKREGU => 'Odwołanie Prezesa Okręgu przez Prezesa Partii',
            self::TYP_POWOLANIE_PO_PREZES_OKREGU => 'Powołanie Pełniącego Obowiązki Prezesa Okręgu przez Prezesa Partii',
            self::TYP_ODWOLANIE_PO_PREZES_OKREGU => 'Odwołanie Pełniącego Obowiązki Prezesa Okręgu przez Prezesa Partii',
            self::TYP_POWOLANIE_SEKRETARZ_OKREGU => 'Powołanie Sekretarza Okręgu przez Prezesa Okręgu',
            self::TYP_ODWOLANIE_SEKRETARZ_OKREGU => 'Odwołanie Sekretarza Okręgu przez Prezesa Okręgu',
            self::TYP_POWOLANIE_SKARBNIK_OKREGU => 'Powołanie Skarbnika Okręgu przez Prezesa Okręgu',
            self::TYP_ODWOLANIE_SKARBNIK_OKREGU => 'Odwołanie Skarbnika Okręgu przez Prezesa Okręgu',
            self::TYP_UTWORZENIE_ODDZIALU => 'Utworzenie Oddziału Przez Zarząd Okręgu',
            self::TYP_WYZNACZENIE_OBSERWATORA => 'Wyznaczenie Obserwatora Zebrania Członków Oddziału',
            self::TYP_WYZNACZENIE_PROTOKOLANTA => 'Wyznaczenie Protokolanta Zebrania Członków Oddziału',
            self::TYP_WYZNACZENIE_PROWADZACEGO => 'Wyznaczenie Prowadzącego Zebrania Członków Oddziału',
            self::TYP_POWOLANIE_PRZEWODNICZACEGO_ODDZIALU => 'Powołanie Przewodniczącego Oddziału przez Zebranie Członków Oddziału',
            self::TYP_ODWOLANIE_PRZEWODNICZACEGO_ODDZIALU => 'Odwołanie Przewodniczącego Oddziału przez Zebranie Członków Oddziału',
            self::TYP_POWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => 'Powołanie Zastępcy Przewodniczącego Oddziału przez Zebranie Członków Oddziału',
            self::TYP_ODWOLANIE_ZASTEPCY_PRZEWODNICZACEGO => 'Odwołanie Zastępcy Przewodniczącego Oddziału przez Zebranie Członków Oddziału',
            self::TYP_POWOLANIE_SEKRETARZA_ODDZIALU => 'Powołanie Sekretarza Oddziału przez Zebranie Członków Oddziału',
            self::TYP_ODWOLANIE_SEKRETARZA_ODDZIALU => 'Odwołanie Sekretarza Oddziału przez Zebranie Członków Oddziału',
            self::TYP_WYBOR_PREZESA_OKREGU_WALNE => 'Wybór Prezesa Okręgu przez Walne Zgromadzenie Członków Okręgu',
            self::TYP_WYBOR_WICEPREZESA_OKREGU_WALNE => 'Wybór Wiceprezesa Okręgu przez Walne Zgromadzenie Członków Okręgu',
            default => 'Nieznany typ',
        };
    }

    /**
     * Generuje hash dokumentu dla weryfikacji integralności.
     */
    public function generateHash(): string
    {
        $content = $this->numerDokumentu.$this->tytul.$this->tresc.
                  $this->dataWejsciaWZycie->format('Y-m-d').
                  ($this->kandydat ? $this->kandydat->getId() : '');

        return hash('sha256', $content);
    }

    /**
     * Weryfikuje integralność dokumentu.
     */
    public function verifyHash(): bool
    {
        return $this->hashDokumentu === $this->generateHash();
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

    public function getZebranieOkregu(): ?ZebranieOkregu
    {
        return $this->zebranieOkregu;
    }

    public function setZebranieOkregu(?ZebranieOkregu $zebranieOkregu): self
    {
        $this->zebranieOkregu = $zebranieOkregu;

        return $this;
    }

    /**
     * Zwraca sformatowaną treść dokumentu dla wyświetlania w PDF.
     */
    public function getFormattedContent(): string
    {
        if (!$this->tresc) {
            return '';
        }

        $content = (string) $this->tresc;

        // Formatowanie nagłówków (linie zaczynające się od UCHWAŁA, §, itd.)
        $content = (string) preg_replace('/^(UCHWAŁA.*)/m', '<div style="font-weight: bold; text-align: center; margin: 20px 0 15px 0; font-size: 13pt;">$1</div>', $content);
        $content = (string) preg_replace('/^(ZARZĄDU OKRĘGU.*|ZARZĄDU KRAJOWEGO.*|OKRĘGOWEGO PEŁNOMOCNIKA.*)/m', '<div style="font-weight: bold; text-align: center; margin: 5px 0; font-size: 12pt;">$1</div>', $content);
        $content = (string) preg_replace('/^(PARTII POLITYCZNEJ NOWA NADZIEJA.*)/m', '<div style="font-weight: bold; text-align: center; margin: 5px 0; font-size: 12pt;">$1</div>', $content);
        $content = (string) preg_replace('/^(z dnia.*)/m', '<div style="text-align: center; margin: 10px 0; font-style: italic;">$1</div>', $content);
        $content = (string) preg_replace('/^(w sprawie.*)/m', '<div style="text-align: center; margin: 10px 0 20px 0; font-weight: bold;">$1</div>', $content);

        // Formatowanie paragrafów (§)
        $content = (string) preg_replace('/^§ (\d+)$/m', '<div style="font-weight: bold; margin: 20px 0 10px 0; font-size: 12pt; border-left: 3px solid #000; padding-left: 10px; background: #f9f9f9;">§ $1</div>', $content);

        // Formatowanie list numerowanych - tylko linie zaczynające się od cyfry z kropką
        $content = (string) preg_replace('/^(\d+\.\s.+)$/m', '<div style="margin: 5px 0 5px 30px; padding: 3px 8px; background: #f8f9fa; border-left: 3px solid #666;">$1</div>', $content);

        // Formatowanie obszaru podpisów - najprostsze podejście bez float
        $content = (string) preg_replace('/^(Przewodniczący.*?)\s+(Sekretarz.*?)$/ms',
            '<div style="margin-top: 40px; page-break-inside: avoid; text-align: center;">$1 &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; $2</div>', $content);

        // Formatowanie linii podpisu - prostsze podejście bez float
        $content = (string) str_replace('_________________________                        _________________________',
            '<div style="margin-top: 30px; text-align: center;">_________________________ &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; _________________________<br>'.
            '<small>(podpis) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (podpis)</small></div>', $content);

        // Zastąp pozostałe nowe linie na <br>
        $content = nl2br($content);

        return $content;
    }

    public function getProtokolantPodpisal(): ?\DateTimeInterface
    {
        return $this->protokolantPodpisal;
    }

    public function setProtokolantPodpisal(?\DateTimeInterface $protokolantPodpisal): self
    {
        $this->protokolantPodpisal = $protokolantPodpisal;
        return $this;
    }

    public function getProwadzacyPodpisal(): ?\DateTimeInterface
    {
        return $this->prowadzacyPodpisal;
    }

    public function setProwadzacyPodpisal(?\DateTimeInterface $prowadzacyPodpisal): self
    {
        $this->prowadzacyPodpisal = $prowadzacyPodpisal;
        return $this;
    }
}
