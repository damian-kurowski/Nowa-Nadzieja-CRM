<?php

namespace App\Entity;

use App\Repository\PodpisDokumentuRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PodpisDokumentuRepository::class)]
class PodpisDokumentu
{
    public const STATUS_OCZEKUJE = 'oczekuje';
    public const STATUS_PODPISANY = 'podpisany';
    public const STATUS_ODRZUCONY = 'odrzucony';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\Dokument', inversedBy: 'podpisy')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Dokument $dokument = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\JoinColumn(nullable: false)]
    private User $podpisujacy;

    #[ORM\Column(length: 50)]
    private string $status = self::STATUS_OCZEKUJE;

    #[ORM\Column(type: Types::INTEGER)]
    private int $kolejnosc;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataUtworzenia;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisania = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $podpisElektroniczny = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $komentarz = null;

    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    private ?string $adresIp = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $hashPodpisu = null;

    public function __construct()
    {
        $this->dataUtworzenia = new \DateTime();
        $this->status = self::STATUS_OCZEKUJE;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDokument(): ?Dokument
    {
        return $this->dokument;
    }

    public function setDokument(?Dokument $dokument): self
    {
        $this->dokument = $dokument;

        return $this;
    }

    public function getPodpisujacy(): User
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

    public function getKolejnosc(): int
    {
        return $this->kolejnosc;
    }

    public function setKolejnosc(int $kolejnosc): self
    {
        $this->kolejnosc = $kolejnosc;

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

    public function getPodpisElektroniczny(): ?string
    {
        return $this->podpisElektroniczny;
    }

    public function setPodpisElektroniczny(?string $podpisElektroniczny): self
    {
        $this->podpisElektroniczny = $podpisElektroniczny;

        return $this;
    }

    public function getKomentarz(): ?string
    {
        return $this->komentarz;
    }

    public function setKomentarz(?string $komentarz): self
    {
        $this->komentarz = $komentarz;

        return $this;
    }

    public function getAdresIp(): ?string
    {
        return $this->adresIp;
    }

    public function setAdresIp(?string $adresIp): self
    {
        $this->adresIp = $adresIp;

        return $this;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): self
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    public function getHashPodpisu(): ?string
    {
        return $this->hashPodpisu;
    }

    public function setHashPodpisu(?string $hashPodpisu): self
    {
        $this->hashPodpisu = $hashPodpisu;

        return $this;
    }

    /**
     * Sprawdza czy podpis został złożony.
     */
    public function isSigned(): bool
    {
        return self::STATUS_PODPISANY === $this->status;
    }

    /**
     * Sprawdza czy podpis został odrzucony.
     */
    public function isRejected(): bool
    {
        return self::STATUS_ODRZUCONY === $this->status;
    }

    /**
     * Sprawdza czy podpis oczekuje na złożenie.
     */
    public function isPending(): bool
    {
        return self::STATUS_OCZEKUJE === $this->status;
    }

    /**
     * Wykonuje podpis elektroniczny.
     */
    public function sign(string $podpisElektroniczny, ?string $komentarz = null, ?string $adresIp = null, ?string $userAgent = null): self
    {
        $this->podpisElektroniczny = $podpisElektroniczny;
        $this->komentarz = $komentarz;
        $this->adresIp = $adresIp;
        $this->userAgent = $userAgent;
        $this->status = self::STATUS_PODPISANY;
        $this->dataPodpisania = new \DateTime();

        // Generuj hash podpisu dla weryfikacji
        $this->hashPodpisu = $this->generateSignatureHash();

        return $this;
    }

    /**
     * Odrzuca podpis z komentarzem.
     */
    public function reject(string $komentarz, ?string $adresIp = null, ?string $userAgent = null): self
    {
        $this->komentarz = $komentarz;
        $this->adresIp = $adresIp;
        $this->userAgent = $userAgent;
        $this->status = self::STATUS_ODRZUCONY;
        $this->dataPodpisania = new \DateTime();

        return $this;
    }

    /**
     * Generuje hash podpisu dla weryfikacji integralności.
     */
    private function generateSignatureHash(): string
    {
        $content = $this->dokument?->getNumerDokumentu().
                  $this->podpisujacy->getId().
                  $this->podpisElektroniczny.
                  ($this->dataPodpisania?->format('Y-m-d H:i:s') ?? '');

        return hash('sha256', $content);
    }

    /**
     * Weryfikuje integralność podpisu.
     */
    public function verifySignatureHash(): bool
    {
        if (!$this->isSigned() || !$this->hashPodpisu) {
            return false;
        }

        return $this->hashPodpisu === $this->generateSignatureHash();
    }

    /**
     * Zwraca kolor badge'a dla statusu.
     */
    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_OCZEKUJE => 'bg-warning',
            self::STATUS_PODPISANY => 'bg-success',
            self::STATUS_ODRZUCONY => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Zwraca nazwę statusu do wyświetlenia.
     */
    public function getStatusDisplayName(): string
    {
        return match ($this->status) {
            self::STATUS_OCZEKUJE => 'Oczekuje na podpis',
            self::STATUS_PODPISANY => 'Podpisany',
            self::STATUS_ODRZUCONY => 'Odrzucony',
            default => 'Nieznany',
        };
    }

    /**
     * Zwraca pełną nazwę podpisującego z funkcją.
     */
    public function getPodpisujacyFullInfo(): string
    {
        $name = $this->podpisujacy->getFullName();

        // Dodaj informacje o funkcji jeśli istnieją - ale tylko jeśli nie spowoduje to nadmiernego ładowania
        try {
            $funkcje = $this->podpisujacy->getFunkcje();
            if (!$funkcje->isEmpty()) {
                $funkcja = $funkcje->first();
                if ($funkcja && method_exists($funkcja, 'getNazwa')) {
                    $nazwa = $funkcja->getNazwa();
                    if ($nazwa) {
                        $name .= ' (' . $nazwa . ')';
                    }
                }
            }
        } catch (\Exception $e) {
            // W przypadku problemu z ładowaniem funkcji, zwróć tylko imię i nazwisko
        }

        return $name;
    }

    /**
     * Sprawdza czy można anulować podpis.
     */
    public function canBeCancelled(): bool
    {
        return $this->isPending()
               && $this->dokument && Dokument::STATUS_CZEKA_NA_PODPIS === $this->dokument->getStatus();
    }
}
