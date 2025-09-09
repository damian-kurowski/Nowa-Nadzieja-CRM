<?php

namespace App\Entity;

use App\Repository\ZebranieOkreguRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ZebranieOkreguRepository::class)]
class ZebranieOkregu
{
    public const STATUS_ROZPOCZETE = 'rozpoczete';
    public const STATUS_WYZNACZANIE_PROTOKOLANTA = 'wyznaczanie_protokolanta';
    public const STATUS_WYZNACZANIE_PROWADZACEGO = 'wyznaczanie_prowadzacego';
    public const STATUS_WYBOR_PREZESA = 'wybor_prezesa';
    public const STATUS_WYBOR_WICEPREZESOW = 'wybor_wiceprezesow';
    public const STATUS_SKLADANIE_PODPISOW = 'skladanie_podpisow';
    public const STATUS_OCZEKUJE_NA_AKCEPTACJE = 'oczekuje_na_akceptacje';
    public const STATUS_ZAKONCZONE = 'zakonczone';
    public const STATUS_ANULOWANE = 'anulowane';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Okreg::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Okreg $okreg;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $obserwator;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $protokolant = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $prowadzacy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $prezesOkregu = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $wiceprezes1 = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $wiceprezes2 = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $sekretarzOkregu = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $skarbnikOkregu = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_ROZPOCZETE;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataUtworzenia;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZakonczenia = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notatki = null;

    /**
     * @var Collection<int, Dokument>
     */
    #[ORM\OneToMany(mappedBy: 'zebranieOkregu', targetEntity: Dokument::class)]
    private Collection $dokumenty;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $utworzonePrzez;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $obserwatorZaakceptowal = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $protokolantZaakceptowal = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $prowadzacyZaakceptowal = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataAkceptacjiObserwatora = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataAkceptacjiProtokolanta = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataAkceptacjiProwadzacego = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $obserwatorPodpisal = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $protokolantPodpisal = false;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $prowadzacyPodpisal = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisuObserwatora = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisuProtokolanta = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisuProwadzacego = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $podpisObserwatora = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $podpisProtokolanta = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $podpisProwadzacego = null;

    public function __construct()
    {
        $this->dokumenty = new ArrayCollection();
        $this->dataUtworzenia = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getObserwator(): User
    {
        return $this->obserwator;
    }

    public function setObserwator(User $obserwator): self
    {
        $this->obserwator = $obserwator;
        return $this;
    }

    public function getProtokolant(): ?User
    {
        return $this->protokolant;
    }

    public function setProtokolant(?User $protokolant): self
    {
        $this->protokolant = $protokolant;
        return $this;
    }

    public function getProwadzacy(): ?User
    {
        return $this->prowadzacy;
    }

    public function setProwadzacy(?User $prowadzacy): self
    {
        $this->prowadzacy = $prowadzacy;
        return $this;
    }

    public function getPrezesOkregu(): ?User
    {
        return $this->prezesOkregu;
    }

    public function setPrezesOkregu(?User $prezesOkregu): self
    {
        $this->prezesOkregu = $prezesOkregu;
        return $this;
    }

    public function getWiceprezes1(): ?User
    {
        return $this->wiceprezes1;
    }

    public function setWiceprezes1(?User $wiceprezes1): self
    {
        $this->wiceprezes1 = $wiceprezes1;
        return $this;
    }

    public function getWiceprezes2(): ?User
    {
        return $this->wiceprezes2;
    }

    public function setWiceprezes2(?User $wiceprezes2): self
    {
        $this->wiceprezes2 = $wiceprezes2;
        return $this;
    }

    public function getSekretarzOkregu(): ?User
    {
        return $this->sekretarzOkregu;
    }

    public function setSekretarzOkregu(?User $sekretarzOkregu): self
    {
        $this->sekretarzOkregu = $sekretarzOkregu;
        return $this;
    }

    public function getSkarbnikOkregu(): ?User
    {
        return $this->skarbnikOkregu;
    }

    public function setSkarbnikOkregu(?User $skarbnikOkregu): self
    {
        $this->skarbnikOkregu = $skarbnikOkregu;
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

    public function getDataUtworzenia(): \DateTimeInterface
    {
        return $this->dataUtworzenia;
    }

    public function setDataUtworzenia(\DateTimeInterface $dataUtworzenia): self
    {
        $this->dataUtworzenia = $dataUtworzenia;
        return $this;
    }

    public function getDataZakonczenia(): ?\DateTimeInterface
    {
        return $this->dataZakonczenia;
    }

    public function setDataZakonczenia(?\DateTimeInterface $dataZakonczenia): self
    {
        $this->dataZakonczenia = $dataZakonczenia;
        return $this;
    }

    public function getNotatki(): ?string
    {
        return $this->notatki;
    }

    public function setNotatki(?string $notatki): self
    {
        $this->notatki = $notatki;
        return $this;
    }

    /**
     * @return Collection<int, Dokument>
     */
    public function getDokumenty(): Collection
    {
        return $this->dokumenty;
    }

    public function addDokument(Dokument $dokument): self
    {
        if (!$this->dokumenty->contains($dokument)) {
            $this->dokumenty->add($dokument);
            $dokument->setZebranieOkregu($this);
        }
        return $this;
    }

    public function removeDokument(Dokument $dokument): self
    {
        if ($this->dokumenty->removeElement($dokument)) {
            if ($dokument->getZebranieOkregu() === $this) {
                $dokument->setZebranieOkregu(null);
            }
        }
        return $this;
    }

    public function getUtworzonyPrzez(): User
    {
        return $this->utworzonePrzez;
    }

    public function setUtworzonyPrzez(User $utworzonePrzez): self
    {
        $this->utworzonePrzez = $utworzonePrzez;
        return $this;
    }

    /**
     * Sprawdza czy użytkownik może wykonać akcję w bieżącym statusie.
     */
    public function canUserPerformAction(User $user, string $action): bool
    {
        return match ($action) {
            'wyznacz_protokolanta' => $this->status === self::STATUS_WYZNACZANIE_PROTOKOLANTA && $user === $this->obserwator,
            'wyznacz_prowadzacego' => $this->status === self::STATUS_WYZNACZANIE_PROWADZACEGO && $user === $this->obserwator,
            'wybor_prezesa' => $this->status === self::STATUS_WYBOR_PREZESA && ($user === $this->prowadzacy || $user === $this->protokolant),
            'wybor_wiceprezesow' => $this->status === self::STATUS_WYBOR_WICEPREZESOW && ($user === $this->prowadzacy || $user === $this->protokolant),
            'podpisz_obserwator' => $this->status === self::STATUS_SKLADANIE_PODPISOW && $user === $this->obserwator && !$this->obserwatorPodpisal,
            'podpisz_protokolant' => $this->status === self::STATUS_SKLADANIE_PODPISOW && $user === $this->protokolant && !$this->protokolantPodpisal,
            'podpisz_prowadzacy' => $this->status === self::STATUS_SKLADANIE_PODPISOW && $user === $this->prowadzacy && !$this->prowadzacyPodpisal,
            'akceptuj_obserwator' => $this->status === self::STATUS_OCZEKUJE_NA_AKCEPTACJE && $user === $this->obserwator && !$this->obserwatorZaakceptowal,
            'akceptuj_protokolant' => $this->status === self::STATUS_OCZEKUJE_NA_AKCEPTACJE && $user === $this->protokolant && !$this->protokolantZaakceptowal,
            'akceptuj_prowadzacy' => $this->status === self::STATUS_OCZEKUJE_NA_AKCEPTACJE && $user === $this->prowadzacy && !$this->prowadzacyZaakceptowal,
            default => false,
        };
    }

    /**
     * Zwraca nazwę statusu do wyświetlenia.
     */
    public function getStatusDisplayName(): string
    {
        return match ($this->status) {
            self::STATUS_ROZPOCZETE => 'Zebranie rozpoczęte',
            self::STATUS_WYZNACZANIE_PROTOKOLANTA => 'Krok 2: Wyznaczanie protokolanta',
            self::STATUS_WYZNACZANIE_PROWADZACEGO => 'Krok 3: Wyznaczanie prowadzącego',
            self::STATUS_WYBOR_PREZESA => 'Krok 4: Wybór Prezesa Okręgu',
            self::STATUS_WYBOR_WICEPREZESOW => 'Krok 5: Wybór Wiceprezesów Okręgu',
            self::STATUS_SKLADANIE_PODPISOW => 'Krok 6: Składanie podpisów uczestników',
            self::STATUS_OCZEKUJE_NA_AKCEPTACJE => 'Krok 7: Oczekuje na akceptację wszystkich uczestników',
            self::STATUS_ZAKONCZONE => 'Zebranie zakończone',
            self::STATUS_ANULOWANE => 'Zebranie anulowane',
            default => 'Nieznany status',
        };
    }

    /**
     * Zwraca kolor badge'a dla statusu.
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_ROZPOCZETE => 'bg-info',
            self::STATUS_WYZNACZANIE_PROTOKOLANTA, self::STATUS_WYZNACZANIE_PROWADZACEGO => 'bg-warning',
            self::STATUS_WYBOR_PREZESA, self::STATUS_WYBOR_WICEPREZESOW => 'bg-primary',
            self::STATUS_OCZEKUJE_NA_AKCEPTACJE => 'bg-secondary',
            self::STATUS_ZAKONCZONE => 'bg-success',
            self::STATUS_ANULOWANE => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Sprawdza czy zebranie jest zakończone.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_ZAKONCZONE;
    }

    /**
     * Sprawdza czy zebranie jest anulowane.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_ANULOWANE;
    }

    /**
     * Sprawdza czy zarząd jest kompletny.
     */
    public function isManagementComplete(): bool
    {
        return $this->prezesOkregu !== null && 
               $this->wiceprezes1 !== null && 
               $this->wiceprezes2 !== null;
    }

    public function getObserwatorZaakceptowal(): bool
    {
        return $this->obserwatorZaakceptowal;
    }

    public function setObserwatorZaakceptowal(bool $obserwatorZaakceptowal): self
    {
        $this->obserwatorZaakceptowal = $obserwatorZaakceptowal;
        if ($obserwatorZaakceptowal) {
            $this->dataAkceptacjiObserwatora = new \DateTime();
        }
        return $this;
    }

    public function getProtokolantZaakceptowal(): bool
    {
        return $this->protokolantZaakceptowal;
    }

    public function setProtokolantZaakceptowal(bool $protokolantZaakceptowal): self
    {
        $this->protokolantZaakceptowal = $protokolantZaakceptowal;
        if ($protokolantZaakceptowal) {
            $this->dataAkceptacjiProtokolanta = new \DateTime();
        }
        return $this;
    }

    public function getProwadzacyZaakceptowal(): bool
    {
        return $this->prowadzacyZaakceptowal;
    }

    public function setProwadzacyZaakceptowal(bool $prowadzacyZaakceptowal): self
    {
        $this->prowadzacyZaakceptowal = $prowadzacyZaakceptowal;
        if ($prowadzacyZaakceptowal) {
            $this->dataAkceptacjiProwadzacego = new \DateTime();
        }
        return $this;
    }

    public function czyWszyscyZaakceptowali(): bool
    {
        return $this->obserwatorZaakceptowal && 
               $this->protokolantZaakceptowal && 
               $this->prowadzacyZaakceptowal;
    }

    public function getDataAkceptacjiObserwatora(): ?\DateTimeInterface
    {
        return $this->dataAkceptacjiObserwatora;
    }

    public function getDataAkceptacjiProtokolanta(): ?\DateTimeInterface
    {
        return $this->dataAkceptacjiProtokolanta;
    }

    public function getDataAkceptacjiProwadzacego(): ?\DateTimeInterface
    {
        return $this->dataAkceptacjiProwadzacego;
    }

    public function getObserwatorPodpisal(): bool
    {
        return $this->obserwatorPodpisal;
    }

    public function setObserwatorPodpisal(bool $obserwatorPodpisal): self
    {
        $this->obserwatorPodpisal = $obserwatorPodpisal;
        if ($obserwatorPodpisal) {
            $this->dataPodpisuObserwatora = new \DateTime();
        }
        return $this;
    }

    public function getProtokolantPodpisal(): bool
    {
        return $this->protokolantPodpisal;
    }

    public function setProtokolantPodpisal(bool $protokolantPodpisal): self
    {
        $this->protokolantPodpisal = $protokolantPodpisal;
        if ($protokolantPodpisal) {
            $this->dataPodpisuProtokolanta = new \DateTime();
        }
        return $this;
    }

    public function getProwadzacyPodpisal(): bool
    {
        return $this->prowadzacyPodpisal;
    }

    public function setProwadzacyPodpisal(bool $prowadzacyPodpisal): self
    {
        $this->prowadzacyPodpisal = $prowadzacyPodpisal;
        if ($prowadzacyPodpisal) {
            $this->dataPodpisuProwadzacego = new \DateTime();
        }
        return $this;
    }

    public function czyWszyscyPodpisali(): bool
    {
        return $this->obserwatorPodpisal && 
               $this->protokolantPodpisal && 
               $this->prowadzacyPodpisal;
    }

    /**
     * Zwraca listę uczestników zebrania.
     * @return array<User>
     */
    public function getUczestnicy(): array
    {
        $uczestnicy = [];
        
        if ($this->obserwator) {
            $uczestnicy[] = $this->obserwator;
        }
        
        if ($this->protokolant) {
            $uczestnicy[] = $this->protokolant;
        }
        
        if ($this->prowadzacy) {
            $uczestnicy[] = $this->prowadzacy;
        }
        
        // Dodaj również wybranych członków zarządu jako uczestników
        if ($this->prezesOkregu) {
            $uczestnicy[] = $this->prezesOkregu;
        }
        
        if ($this->wiceprezes1) {
            $uczestnicy[] = $this->wiceprezes1;
        }
        
        if ($this->wiceprezes2) {
            $uczestnicy[] = $this->wiceprezes2;
        }
        
        // Zwróć unikalnych uczestników (aby uniknąć duplikatów)
        return array_unique($uczestnicy, SORT_REGULAR);
    }

    public function getDataPodpisuObserwatora(): ?\DateTimeInterface
    {
        return $this->dataPodpisuObserwatora;
    }

    public function getDataPodpisuProtokolanta(): ?\DateTimeInterface
    {
        return $this->dataPodpisuProtokolanta;
    }

    public function getDataPodpisuProwadzacego(): ?\DateTimeInterface
    {
        return $this->dataPodpisuProwadzacego;
    }

    public function getCurrentStepNumber(): int
    {
        return match ($this->status) {
            self::STATUS_ROZPOCZETE => 1,
            self::STATUS_WYZNACZANIE_PROTOKOLANTA => 2,
            self::STATUS_WYZNACZANIE_PROWADZACEGO => 3,
            self::STATUS_WYBOR_PREZESA => 4,
            self::STATUS_WYBOR_WICEPREZESOW => 5,
            self::STATUS_SKLADANIE_PODPISOW => 6,
            self::STATUS_OCZEKUJE_NA_AKCEPTACJE => 7,
            self::STATUS_ZAKONCZONE => 8,
            default => 0,
        };
    }

    public function getNextStepDescription(): ?string
    {
        return match ($this->status) {
            self::STATUS_ROZPOCZETE => 'Obserwator musi wyznaczyć protokolanta spośród członków okręgu',
            self::STATUS_WYZNACZANIE_PROTOKOLANTA => 'Obserwator musi wyznaczyć prowadzącego spośród członków okręgu',
            self::STATUS_WYZNACZANIE_PROWADZACEGO => 'Prowadzący i Protokolant wspólnie wybierają Prezesa Okręgu',
            self::STATUS_WYBOR_PREZESA => 'Prowadzący i Protokolant wspólnie wybierają do dwóch Wiceprezesów',
            self::STATUS_WYBOR_WICEPREZESOW => 'Wszyscy uczestnicy muszą złożyć swoje podpisy przed generowaniem dokumentów',
            self::STATUS_SKLADANIE_PODPISOW => 'Po złożeniu wszystkich podpisów system wygeneruje dokumenty do akceptacji',
            self::STATUS_OCZEKUJE_NA_AKCEPTACJE => 'Zebranie zostanie automatycznie zamknięte po otrzymaniu wszystkich akceptacji',
            default => null,
        };
    }

    public function canProgressToNextStep(): bool
    {
        return match ($this->status) {
            self::STATUS_ROZPOCZETE => true,
            self::STATUS_WYZNACZANIE_PROTOKOLANTA => $this->protokolant !== null,
            self::STATUS_WYZNACZANIE_PROWADZACEGO => $this->prowadzacy !== null,
            self::STATUS_WYBOR_PREZESA => $this->prezesOkregu !== null,
            self::STATUS_WYBOR_WICEPREZESOW => $this->wiceprezes1 !== null && $this->wiceprezes2 !== null,
            self::STATUS_SKLADANIE_PODPISOW => $this->czyWszyscyPodpisali(),
            self::STATUS_OCZEKUJE_NA_AKCEPTACJE => $this->czyWszyscyZaakceptowali(),
            default => false,
        };
    }

    public function getPodpisObserwatora(): ?string
    {
        return $this->podpisObserwatora;
    }

    public function setPodpisObserwatora(?string $podpisObserwatora): self
    {
        $this->podpisObserwatora = $podpisObserwatora;
        return $this;
    }

    public function getPodpisProtokolanta(): ?string
    {
        return $this->podpisProtokolanta;
    }

    public function setPodpisProtokolanta(?string $podpisProtokolanta): self
    {
        $this->podpisProtokolanta = $podpisProtokolanta;
        return $this;
    }

    public function getPodpisProwadzacego(): ?string
    {
        return $this->podpisProwadzacego;
    }

    public function setPodpisProwadzacego(?string $podpisProwadzacego): self
    {
        $this->podpisProwadzacego = $podpisProwadzacego;
        return $this;
    }
}