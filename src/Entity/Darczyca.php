<?php

namespace App\Entity;

use App\Repository\DarczycaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DarczycaRepository::class)]
class Darczyca
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $imie = null;

    #[ORM\Column(length: 255)]
    private ?string $nazwisko = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firma = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telefon = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adres = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $kwota_wsparcia = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $forma_wsparcia = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $data_pierwszej_wplaty = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $data_ostatniej_wplaty = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notatka = null;

    #[ORM\Column(type: 'boolean')]
    private bool $zgoda_na_kontakt = true;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(length: 20, options: ['default' => 'aktywny'])]
    private string $status_darczyny = 'aktywny';

    #[ORM\Column(length: 20, options: ['default' => 'osoba_fizyczna'])]
    private string $typ_darczyny = 'osoba_fizyczna';

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $nip = null;

    #[ORM\Column(length: 15, nullable: true)]
    private ?string $regon = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $data_urodzenia = null;

    #[ORM\Column(length: 100, options: ['default' => 'email'])]
    private string $preferencje_kontakt = 'email';

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $zgoda_na_newsletter = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $zgoda_na_marketing = false;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, options: ['default' => '0.00'])]
    private string $laczna_kwota_dotacji = '0.00';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $liczba_dotacji = 0;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $ulubiona_metoda_platnosci = null;

    #[ORM\Column(type: 'integer', options: ['default' => 5])]
    private int $rating_darczyny = 5;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $ostatnia_interakcja = null;

    #[ORM\OneToMany(targetEntity: 'App\Entity\Platnosc', mappedBy: 'darczyca')]
    private Collection $platnosci;

    public function __construct()
    {
        $this->created_at = new \DateTime();
        $this->ostatnia_interakcja = new \DateTime();
        $this->platnosci = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImie(): ?string
    {
        return $this->imie;
    }

    public function setImie(string $imie): self
    {
        $this->imie = $imie;
        return $this;
    }

    public function getNazwisko(): ?string
    {
        return $this->nazwisko;
    }

    public function setNazwisko(string $nazwisko): self
    {
        $this->nazwisko = $nazwisko;
        return $this;
    }

    public function getFirma(): ?string
    {
        return $this->firma;
    }

    public function setFirma(?string $firma): self
    {
        $this->firma = $firma;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getTelefon(): ?string
    {
        return $this->telefon;
    }

    public function setTelefon(?string $telefon): self
    {
        $this->telefon = $telefon;
        return $this;
    }

    public function getAdres(): ?string
    {
        return $this->adres;
    }

    public function setAdres(?string $adres): self
    {
        $this->adres = $adres;
        return $this;
    }

    public function getKwotaWsparcia(): ?string
    {
        return $this->kwota_wsparcia;
    }

    public function setKwotaWsparcia(?string $kwota_wsparcia): self
    {
        $this->kwota_wsparcia = $kwota_wsparcia;
        return $this;
    }

    public function getFormaWsparcia(): ?string
    {
        return $this->forma_wsparcia;
    }

    public function setFormaWsparcia(?string $forma_wsparcia): self
    {
        $this->forma_wsparcia = $forma_wsparcia;
        return $this;
    }

    public function getDataPierwszejWplaty(): ?\DateTimeInterface
    {
        return $this->data_pierwszej_wplaty;
    }

    public function setDataPierwszejWplaty(?\DateTimeInterface $data_pierwszej_wplaty): self
    {
        $this->data_pierwszej_wplaty = $data_pierwszej_wplaty;
        return $this;
    }

    public function getDataOstatniejWplaty(): ?\DateTimeInterface
    {
        return $this->data_ostatniej_wplaty;
    }

    public function setDataOstatniejWplaty(?\DateTimeInterface $data_ostatniej_wplaty): self
    {
        $this->data_ostatniej_wplaty = $data_ostatniej_wplaty;
        return $this;
    }

    public function getNotatka(): ?string
    {
        return $this->notatka;
    }

    public function setNotatka(?string $notatka): self
    {
        $this->notatka = $notatka;
        return $this;
    }

    public function isZgodaNaKontakt(): bool
    {
        return $this->zgoda_na_kontakt;
    }

    public function setZgodaNaKontakt(bool $zgoda_na_kontakt): self
    {
        $this->zgoda_na_kontakt = $zgoda_na_kontakt;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): self
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getStatusDarczyny(): string
    {
        return $this->status_darczyny;
    }

    public function setStatusDarczyny(string $status_darczyny): self
    {
        $this->status_darczyny = $status_darczyny;
        return $this;
    }

    public function getTypDarczyny(): string
    {
        return $this->typ_darczyny;
    }

    public function setTypDarczyny(string $typ_darczyny): self
    {
        $this->typ_darczyny = $typ_darczyny;
        return $this;
    }

    public function getNip(): ?string
    {
        return $this->nip;
    }

    public function setNip(?string $nip): self
    {
        $this->nip = $nip;
        return $this;
    }

    public function getRegon(): ?string
    {
        return $this->regon;
    }

    public function setRegon(?string $regon): self
    {
        $this->regon = $regon;
        return $this;
    }

    public function getDataUrodzenia(): ?\DateTimeInterface
    {
        return $this->data_urodzenia;
    }

    public function setDataUrodzenia(?\DateTimeInterface $data_urodzenia): self
    {
        $this->data_urodzenia = $data_urodzenia;
        return $this;
    }

    public function getPreferencjeKontakt(): string
    {
        return $this->preferencje_kontakt;
    }

    public function setPreferencjeKontakt(string $preferencje_kontakt): self
    {
        $this->preferencje_kontakt = $preferencje_kontakt;
        return $this;
    }

    public function isZgodaNaNewsletter(): bool
    {
        return $this->zgoda_na_newsletter;
    }

    public function setZgodaNaNewsletter(bool $zgoda_na_newsletter): self
    {
        $this->zgoda_na_newsletter = $zgoda_na_newsletter;
        return $this;
    }

    public function isZgodaNaMarketing(): bool
    {
        return $this->zgoda_na_marketing;
    }

    public function setZgodaNaMarketing(bool $zgoda_na_marketing): self
    {
        $this->zgoda_na_marketing = $zgoda_na_marketing;
        return $this;
    }

    public function getLacznaKwotaDotacji(): string
    {
        return $this->laczna_kwota_dotacji;
    }

    public function setLacznaKwotaDotacji(string $laczna_kwota_dotacji): self
    {
        $this->laczna_kwota_dotacji = $laczna_kwota_dotacji;
        return $this;
    }

    public function getLiczbaDotacji(): int
    {
        return $this->liczba_dotacji;
    }

    public function setLiczbaDotacji(int $liczba_dotacji): self
    {
        $this->liczba_dotacji = $liczba_dotacji;
        return $this;
    }

    public function getUlubioneMetodaPlatnosci(): ?string
    {
        return $this->ulubiona_metoda_platnosci;
    }

    public function setUlubioneMetodaPlatnosci(?string $ulubiona_metoda_platnosci): self
    {
        $this->ulubiona_metoda_platnosci = $ulubiona_metoda_platnosci;
        return $this;
    }

    public function getRatingDarczyny(): int
    {
        return $this->rating_darczyny;
    }

    public function setRatingDarczyny(int $rating_darczyny): self
    {
        $this->rating_darczyny = $rating_darczyny;
        return $this;
    }

    public function getOstatniaInterakcja(): ?\DateTimeInterface
    {
        return $this->ostatnia_interakcja;
    }

    public function setOstatniaInterakcja(?\DateTimeInterface $ostatnia_interakcja): self
    {
        $this->ostatnia_interakcja = $ostatnia_interakcja;
        return $this;
    }

    /**
     * @return Collection<int, Platnosc>
     */
    public function getPlatnosci(): Collection
    {
        return $this->platnosci;
    }

    public function addPlatnosc(Platnosc $platnosc): self
    {
        if (!$this->platnosci->contains($platnosc)) {
            $this->platnosci->add($platnosc);
            $platnosc->setDarczyca($this);
        }

        return $this;
    }

    public function removePlatnosc(Platnosc $platnosc): self
    {
        if ($this->platnosci->removeElement($platnosc)) {
            if ($platnosc->getDarczyca() === $this) {
                $platnosc->setDarczyca(null);
            }
        }

        return $this;
    }

    public function getPelneImieNazwisko(): string
    {
        if ($this->firma) {
            return $this->firma . ' (' . $this->imie . ' ' . $this->nazwisko . ')';
        }
        return $this->imie . ' ' . $this->nazwisko;
    }

    public function getSrednieWsparcie(): float
    {
        if ($this->liczba_dotacji === 0) {
            return 0.0;
        }
        return (float)$this->laczna_kwota_dotacji / $this->liczba_dotacji;
    }

    public function getStatusBadge(): string
    {
        return match($this->status_darczyny) {
            'aktywny' => 'success',
            'nieaktywny' => 'secondary',
            'vip' => 'warning',
            'zablokowany' => 'danger',
            default => 'primary'
        };
    }
}
