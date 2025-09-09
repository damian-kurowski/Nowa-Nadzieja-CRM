<?php

namespace App\Entity;

use App\Repository\ZebranieOddzialuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ZebranieOddzialuRepository::class)]
class ZebranieOddzialu
{
    public const STATUS_OCZEKUJE_NA_PROTOKOLANTA = 'oczekuje_na_protokolanta';
    public const STATUS_OCZEKUJE_NA_PROWADZACEGO = 'oczekuje_na_prowadzacego';
    public const STATUS_WYBOR_PRZEWODNICZACEGO = 'wybor_przewodniczacego';
    public const STATUS_WYBOR_ZASTEPCOW = 'wybor_zastepcow';
    public const STATUS_WYBOR_ZARZADU = 'wybor_zarzadu';
    public const STATUS_OCZEKUJE_NA_PODPISY = 'oczekuje_na_podpisy';
    public const STATUS_ZAKONCZONE = 'zakonczone';
    public const STATUS_ANULOWANE = 'anulowane';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Oddzial::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Oddzial $oddzial;

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
    private ?User $przewodniczacy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $zastepca1 = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $zastepca2 = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $sekretarz = null;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_OCZEKUJE_NA_PROTOKOLANTA;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataRozpoczecia;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZakonczenia = null;

    /**
     * @var Collection<int, Dokument>
     */
    #[ORM\OneToMany(mappedBy: 'zebranieOddzialu', targetEntity: Dokument::class)]
    private Collection $dokumenty;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisuObserwatora = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisuProtokolanta = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisuProwadzacego = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisuPrzewodniczacego = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisuZastepcy1 = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisuZastepcy2 = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $krotkiProtokol = null;

    public function __construct()
    {
        $this->dokumenty = new ArrayCollection();
        $this->dataRozpoczecia = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOddzial(): Oddzial
    {
        return $this->oddzial;
    }

    public function setOddzial(Oddzial $oddzial): self
    {
        $this->oddzial = $oddzial;

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

    public function getPrzewodniczacy(): ?User
    {
        return $this->przewodniczacy;
    }

    public function setPrzewodniczacy(?User $przewodniczacy): self
    {
        $this->przewodniczacy = $przewodniczacy;

        return $this;
    }

    public function getZastepca1(): ?User
    {
        return $this->zastepca1;
    }

    public function setZastepca1(?User $zastepca1): self
    {
        $this->zastepca1 = $zastepca1;

        return $this;
    }

    public function getZastepca2(): ?User
    {
        return $this->zastepca2;
    }

    public function setZastepca2(?User $zastepca2): self
    {
        $this->zastepca2 = $zastepca2;

        return $this;
    }

    public function getSekretarz(): ?User
    {
        return $this->sekretarz;
    }

    public function setSekretarz(?User $sekretarz): self
    {
        $this->sekretarz = $sekretarz;

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

    public function getDataRozpoczecia(): \DateTimeInterface
    {
        return $this->dataRozpoczecia;
    }

    public function setDataRozpoczecia(\DateTimeInterface $dataRozpoczecia): self
    {
        $this->dataRozpoczecia = $dataRozpoczecia;

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
            $dokument->setZebranieOddzialu($this);
        }

        return $this;
    }

    public function removeDokument(Dokument $dokument): self
    {
        if ($this->dokumenty->removeElement($dokument)) {
            if ($dokument->getZebranieOddzialu() === $this) {
                $dokument->setZebranieOddzialu(null);
            }
        }

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getKrotkiProtokol(): ?array
    {
        return $this->krotkiProtokol;
    }

    /**
     * @param array<string, mixed>|null $krotkiProtokol
     */
    public function setKrotkiProtokol(?array $krotkiProtokol): self
    {
        $this->krotkiProtokol = $krotkiProtokol;

        return $this;
    }

    public function isAktywne(): bool
    {
        return !in_array($this->status, [self::STATUS_ZAKONCZONE, self::STATUS_ANULOWANE]);
    }

    public function isZakonczone(): bool
    {
        return self::STATUS_ZAKONCZONE === $this->status;
    }

    public function zakonczZebranie(): void
    {
        $this->status = self::STATUS_ZAKONCZONE;
        $this->dataZakonczenia = new \DateTime();
    }

    public function getStatusDisplayName(): string
    {
        return match ($this->status) {
            self::STATUS_OCZEKUJE_NA_PROTOKOLANTA => 'Oczekiwanie na protokolanta',
            self::STATUS_OCZEKUJE_NA_PROWADZACEGO => 'Oczekiwanie na prowadzącego',
            self::STATUS_WYBOR_PRZEWODNICZACEGO => 'Wybór przewodniczącego zebrania',
            self::STATUS_WYBOR_ZASTEPCOW => 'Wybór zastępców przewodniczącego',
            self::STATUS_WYBOR_ZARZADU => 'Wybór zarządu',
            self::STATUS_OCZEKUJE_NA_PODPISY => 'Oczekiwanie na podpisy',
            self::STATUS_ZAKONCZONE => 'Zakończone',
            self::STATUS_ANULOWANE => 'Anulowane',
            default => 'Nieznany',
        };
    }

    public function getCurrentStep(): string
    {
        return match ($this->status) {
            self::STATUS_OCZEKUJE_NA_PROTOKOLANTA => 'protokolant',
            self::STATUS_OCZEKUJE_NA_PROWADZACEGO => 'prowadzacy',
            self::STATUS_WYBOR_PRZEWODNICZACEGO => 'przewodniczacy',
            self::STATUS_WYBOR_ZASTEPCOW => 'zastepcy',
            self::STATUS_WYBOR_ZARZADU => 'zarzad',
            self::STATUS_OCZEKUJE_NA_PODPISY => 'podpisy',
            self::STATUS_ZAKONCZONE => 'zakonczone',
            default => 'nieznany',
        };
    }
    
    public function getCurrentStepNumber(): int
    {
        return match ($this->status) {
            self::STATUS_OCZEKUJE_NA_PROTOKOLANTA => 2,
            self::STATUS_OCZEKUJE_NA_PROWADZACEGO => 3,
            self::STATUS_WYBOR_PRZEWODNICZACEGO => 4,
            self::STATUS_WYBOR_ZASTEPCOW => 5,
            self::STATUS_WYBOR_ZARZADU => 6,
            self::STATUS_OCZEKUJE_NA_PODPISY => 6,
            self::STATUS_ZAKONCZONE => 7,
            default => 1,
        };
    }

    public function canAssignProtokolant(User $user): bool
    {
        return $this->obserwator === $user && $this->status === self::STATUS_OCZEKUJE_NA_PROTOKOLANTA;
    }

    public function canAssignProwadzacy(User $user): bool
    {
        return $this->obserwator === $user && $this->status === self::STATUS_OCZEKUJE_NA_PROWADZACEGO;
    }

    public function canSelectPrzewodniczacy(User $user): bool
    {
        return ($this->protokolant === $user || $this->prowadzacy === $user) 
            && $this->status === self::STATUS_WYBOR_PRZEWODNICZACEGO;
    }

    public function canSelectZastepcy(User $user): bool
    {
        return $this->przewodniczacy === $user && $this->status === self::STATUS_WYBOR_ZASTEPCOW;
    }

    public function canManagePositions(User $user): bool
    {
        return ($this->prowadzacy === $user || $this->protokolant === $user) 
            && $this->status === self::STATUS_WYBOR_ZARZADU;
    }

    public function getSignedDocumentsCount(): int
    {
        $count = 0;
        foreach ($this->dokumenty as $dokument) {
            if ($dokument->getStatus() === Dokument::STATUS_PODPISANY) {
                $count++;
            }
        }
        return $count;
    }
    
    public function getTotalDocumentsCount(): int
    {
        return $this->dokumenty->count();
    }
    
    public function getAllPendingDocuments(): int
    {
        $count = 0;
        foreach ($this->dokumenty as $dokument) {
            if ($dokument->getStatus() !== 'podpisany') {
                $count++;
            }
        }
        return $count;
    }

    // Signature management methods
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

    public function getDataPodpisuPrzewodniczacego(): ?\DateTimeInterface
    {
        return $this->dataPodpisuPrzewodniczacego;
    }

    public function getDataPodpisuZastepcy1(): ?\DateTimeInterface
    {
        return $this->dataPodpisuZastepcy1;
    }

    public function getDataPodpisuZastepcy2(): ?\DateTimeInterface
    {
        return $this->dataPodpisuZastepcy2;
    }

    public function obserwatorPodpisal(): bool
    {
        return $this->dataPodpisuObserwatora !== null;
    }

    public function protokolantPodpisal(): bool
    {
        return $this->dataPodpisuProtokolanta !== null;
    }

    public function prowadzacyPodpisal(): bool
    {
        return $this->dataPodpisuProwadzacego !== null;
    }

    public function przewodniczacyPodpisal(): bool
    {
        return $this->dataPodpisuPrzewodniczacego !== null;
    }

    public function zastepca1Podpisal(): bool
    {
        return $this->dataPodpisuZastepcy1 !== null;
    }

    public function zastepca2Podpisal(): bool
    {
        return $this->dataPodpisuZastepcy2 !== null;
    }

    public function podpiszJakoObserwator(): void
    {
        $this->dataPodpisuObserwatora = new \DateTime();
    }

    public function podpiszJakoProtokolant(): void
    {
        $this->dataPodpisuProtokolanta = new \DateTime();
    }

    public function podpiszJakoProwadzacy(): void
    {
        $this->dataPodpisuProwadzacego = new \DateTime();
    }

    public function podpiszJakoPrzewodniczacy(): void
    {
        $this->dataPodpisuPrzewodniczacego = new \DateTime();
    }

    public function podpiszJakoZastepca1(): void
    {
        $this->dataPodpisuZastepcy1 = new \DateTime();
    }

    public function podpiszJakoZastepca2(): void
    {
        $this->dataPodpisuZastepcy2 = new \DateTime();
    }

    public function czyWszyscyPodpisali(): bool
    {
        $wymaganiUczestnicy = [];
        
        if ($this->obserwator) {
            $wymaganiUczestnicy[] = $this->obserwatorPodpisal();
        }
        if ($this->protokolant) {
            $wymaganiUczestnicy[] = $this->protokolantPodpisal();
        }
        if ($this->prowadzacy) {
            $wymaganiUczestnicy[] = $this->prowadzacyPodpisal();
        }
        if ($this->przewodniczacy) {
            $wymaganiUczestnicy[] = $this->przewodniczacyPodpisal();
        }
        if ($this->zastepca1) {
            $wymaganiUczestnicy[] = $this->zastepca1Podpisal();
        }
        if ($this->zastepca2) {
            $wymaganiUczestnicy[] = $this->zastepca2Podpisal();
        }

        return !empty($wymaganiUczestnicy) && !in_array(false, $wymaganiUczestnicy, true);
    }

    public function canUserPerformAction(User $user, string $action): bool
    {
        return match($action) {
            'podpisz_obserwator' => $this->obserwator === $user && !$this->obserwatorPodpisal() && $this->status === self::STATUS_OCZEKUJE_NA_PODPISY,
            'podpisz_protokolant' => $this->protokolant === $user && !$this->protokolantPodpisal() && $this->status === self::STATUS_OCZEKUJE_NA_PODPISY,
            'podpisz_prowadzacy' => $this->prowadzacy === $user && !$this->prowadzacyPodpisal() && $this->status === self::STATUS_OCZEKUJE_NA_PODPISY,
            'podpisz_przewodniczacy' => $this->przewodniczacy === $user && !$this->przewodniczacyPodpisal() && $this->status === self::STATUS_OCZEKUJE_NA_PODPISY,
            'podpisz_zastepca1' => $this->zastepca1 === $user && !$this->zastepca1Podpisal() && $this->status === self::STATUS_OCZEKUJE_NA_PODPISY,
            'podpisz_zastepca2' => $this->zastepca2 === $user && !$this->zastepca2Podpisal() && $this->status === self::STATUS_OCZEKUJE_NA_PODPISY,
            default => false,
        };
    }

    public function __toString(): string
    {
        return sprintf(
            'Zebranie oddziału %s (%s)',
            $this->oddzial->getNazwa(),
            $this->dataRozpoczecia->format('Y-m-d H:i')
        );
    }
}
