<?php

namespace App\Entity;

use App\Repository\FunkcjaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FunkcjaRepository::class)]
class Funkcja
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'funkcje')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $nazwa; // 'prezes_partii', 'wiceprezes_partii', 'sekretarz_partii', etc.

    #[ORM\Column(length: 50)]
    private string $poziom; // 'krajowy', 'okreg', 'oddzial'

    #[ORM\Column(length: 50)]
    private string $organizacja; // 'partia', 'mlodziezowka'

    #[ORM\ManyToOne(targetEntity: Okreg::class)]
    private ?Okreg $okreg = null;

    #[ORM\ManyToOne(targetEntity: Oddzial::class)]
    private ?Oddzial $oddzial = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataRozpoczecia;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZakonczenia = null;

    #[ORM\Column]
    private bool $aktywna = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getNazwa(): string
    {
        return $this->nazwa;
    }

    public function setNazwa(string $nazwa): self
    {
        $this->nazwa = $nazwa;

        return $this;
    }

    /**
     * 'krajowy' | 'okreg' | 'oddzial'.
     */
    public function getPoziom(): string
    {
        return $this->poziom;
    }

    public function setPoziom(string $poziom): self
    {
        $this->poziom = $poziom;

        return $this;
    }

    /**
     * 'partia' | 'mlodziezowka'.
     */
    public function getOrganizacja(): string
    {
        return $this->organizacja;
    }

    public function setOrganizacja(string $organizacja): self
    {
        $this->organizacja = $organizacja;

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

    public function isAktywna(): bool
    {
        return (bool) $this->aktywna;
    }

    public function setAktywna(bool $aktywna): self
    {
        $this->aktywna = $aktywna;

        return $this;
    }

    /**
     * Zwraca czytelną nazwę funkcji do wyświetlenia.
     */
    public function getDisplayName(): string
    {
        $functionNames = [
            // Role krajowe (partyjne)
            'prezes_partii' => 'Prezes Partii',
            'wiceprezes_partii' => 'Wiceprezes Partii',
            'sekretarz_partii' => 'Sekretarz Partii',
            'skarbnik_partii' => 'Skarbnik Partii',
            'rzecznik_prasowy' => 'Rzecznik Prasowy',
            'pelnomocnik_struktur' => 'Pełnomocnik ds. Struktur',

            // Role okręgowe
            'prezes_okregu' => 'Prezes Okręgu',
            'wiceprezes_okregu' => 'Wiceprezes Okręgu',
            'sekretarz_okregu' => 'Sekretarz Okręgu',
            'skarbnik_okregu' => 'Skarbnik Okręgu',
            'informatyk_okregowy' => 'Informatyk Okręgowy',
            'okregowy_pelnomocnik_ds_przyjmowania_czlonkow' => 'Okręgowy Pełnomocnik ds. Przyjmowania Członków',
            'czlonek_zarzadu_okregu' => 'Członek Zarządu Okręgu',

            // Role oddziałowe
            'przewodniczacy_oddzialu' => 'Przewodniczący Oddziału',
            'zastepca_przewodniczacego_oddzialu' => 'Zastępca Przewodniczącego Oddziału',
            'sekretarz_oddzialu' => 'Sekretarz Oddziału',
            'skarbnik_oddzialu' => 'Skarbnik Oddziału',
            'czlonek_rady_oddzialu' => 'Członek Rady Oddziału',

            // Role młodzieżówki
            'prezes_mlodziezowki' => 'Prezes Młodzieżówki',
            'czlonek_zarzadu_mlodziezowki' => 'Członek Zarządu Młodzieżówki',
            'sekretarz_mlodziezowki' => 'Sekretarz Młodzieżówki',
            'lider_mlodziezowki' => 'Lider Młodzieżówki',
            // Role dodatkowe
            'aktywista' => 'Aktywista',
        ];

        $displayName = $functionNames[$this->nazwa] ?? ucfirst(str_replace('_', ' ', $this->nazwa));

        // Dodaj informację o okręgu/oddziale jeśli istnieje
        if ($this->okreg && 'okreg' === $this->poziom) {
            $displayName .= ' ('.$this->okreg->getNazwa().')';
        } elseif ($this->oddzial && 'oddzial' === $this->poziom) {
            $displayName .= ' ('.$this->oddzial->getNazwa().')';
        }

        return $displayName;
    }
}
