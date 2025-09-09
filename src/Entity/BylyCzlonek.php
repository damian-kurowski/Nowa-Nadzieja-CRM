<?php

namespace App\Entity;

use App\Repository\BylyCzlonekRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BylyCzlonekRepository::class)]
class BylyCzlonek
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $imie;

    #[ORM\Column(name: 'drugie_imie', length: 255, nullable: true)]
    private ?string $drugieImie = null;

    #[ORM\Column(length: 255)]
    private string $nazwisko;

    #[ORM\Column(length: 11, nullable: true)]
    private ?string $pesel = null;

    #[ORM\Column(name: 'data_urodzenia', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataUrodzenia = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $plec = null;

    #[ORM\Column(length: 255)]
    private string $email;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $telefon = null;

    #[ORM\Column(name: 'adres_zamieszkania', type: Types::TEXT, nullable: true)]
    private ?string $adresZamieszkania = null;

    #[ORM\Column(name: 'zatrudnienie_współkach', type: Types::TEXT, nullable: true)]
    private ?string $zatrudnienieWSpółkach = null;

    #[ORM\Column(name: 'social_media', type: Types::TEXT, nullable: true)]
    private ?string $socialMedia = null;

    #[ORM\Column(name: 'informacje_omnie', type: Types::TEXT, nullable: true)]
    private ?string $informacjeOMnie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zdjecie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\ManyToOne(targetEntity: Okreg::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Okreg $okreg;

    #[ORM\ManyToOne(targetEntity: Oddzial::class)]
    private ?Oddzial $oddzial = null;

    #[ORM\Column(name: 'rodzaj_czlonkostwa', length: 50)]
    private string $rodzajCzlonkostwa;

    #[ORM\Column(name: 'data_przyjecia', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPrzyjecia = null;

    #[ORM\Column(name: 'data_zlozenia_deklaracji', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZlozeniaDeklaracji = null;

    #[ORM\Column(name: 'numer_wpartii', length: 255, nullable: true)]
    private ?string $numerWPartii = null;

    #[ORM\Column(name: 'dodatkowe_informacje', type: Types::TEXT, nullable: true)]
    private ?string $dodatkoweInformacje = null;

    #[ORM\Column(name: 'notatka_wewnetrzna', type: Types::TEXT, nullable: true)]
    private ?string $notatkaWewnetrzna = null;

    #[ORM\Column(name: 'old_id', length: 255, nullable: true)]
    private ?string $oldId = null;

    #[ORM\Column(name: 'powod_zakonczenia_czlonkostwa', type: Types::TEXT, nullable: true)]
    private ?string $powodZakonczeniaCzlonkostwa = null;

    #[ORM\Column(name: 'data_zakonczenia_czlonkostwa', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZakonczeniaCzlonkostwa = null;

    #[ORM\Column(name: 'oryginalny_id_czlonka', type: Types::INTEGER, nullable: true)]
    private ?int $oryginalnyIdCzlonka = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImie(): string
    {
        return $this->imie;
    }

    public function setImie(string $imie): self
    {
        $this->imie = $imie;

        return $this;
    }

    public function getDrugieImie(): ?string
    {
        return $this->drugieImie;
    }

    public function setDrugieImie(?string $drugieImie): self
    {
        $this->drugieImie = $drugieImie;

        return $this;
    }

    public function getNazwisko(): string
    {
        return $this->nazwisko;
    }

    public function setNazwisko(string $nazwisko): self
    {
        $this->nazwisko = $nazwisko;

        return $this;
    }

    public function getPesel(): ?string
    {
        return $this->pesel;
    }

    public function setPesel(?string $pesel): self
    {
        $this->pesel = $pesel;

        return $this;
    }

    public function getDataUrodzenia(): ?\DateTimeInterface
    {
        return $this->dataUrodzenia;
    }

    public function setDataUrodzenia(?\DateTimeInterface $dataUrodzenia): self
    {
        $this->dataUrodzenia = $dataUrodzenia;

        return $this;
    }

    public function getPlec(): ?string
    {
        return $this->plec;
    }

    public function setPlec(?string $plec): self
    {
        $this->plec = $plec;

        return $this;
    }

    public function getEmail(): string
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

    public function getAdresZamieszkania(): ?string
    {
        return $this->adresZamieszkania;
    }

    public function setAdresZamieszkania(?string $adresZamieszkania): self
    {
        $this->adresZamieszkania = $adresZamieszkania;

        return $this;
    }

    public function getZatrudnienieWSpółkach(): ?string
    {
        return $this->zatrudnienieWSpółkach;
    }

    public function setZatrudnienieWSpółkach(?string $zatrudnienieWSpółkach): self
    {
        $this->zatrudnienieWSpółkach = $zatrudnienieWSpółkach;

        return $this;
    }

    public function getSocialMedia(): ?string
    {
        return $this->socialMedia;
    }

    public function setSocialMedia(?string $socialMedia): self
    {
        $this->socialMedia = $socialMedia;

        return $this;
    }

    public function getInformacjeOMnie(): ?string
    {
        return $this->informacjeOMnie;
    }

    public function setInformacjeOMnie(?string $informacjeOMnie): self
    {
        $this->informacjeOMnie = $informacjeOMnie;

        return $this;
    }

    public function getZdjecie(): ?string
    {
        return $this->zdjecie;
    }

    public function setZdjecie(?string $zdjecie): self
    {
        $this->zdjecie = $zdjecie;

        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(?string $cv): self
    {
        $this->cv = $cv;

        return $this;
    }

    public function getOkreg(): ?Okreg
    {
        return $this->okreg ?? null;
    }

    public function setOkreg(Okreg $okreg): self
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

    public function getRodzajCzlonkostwa(): string
    {
        return $this->rodzajCzlonkostwa;
    }

    public function setRodzajCzlonkostwa(string $rodzajCzlonkostwa): self
    {
        $this->rodzajCzlonkostwa = $rodzajCzlonkostwa;

        return $this;
    }

    public function getDataPrzyjecia(): ?\DateTimeInterface
    {
        return $this->dataPrzyjecia;
    }

    public function setDataPrzyjecia(?\DateTimeInterface $dataPrzyjecia): self
    {
        $this->dataPrzyjecia = $dataPrzyjecia;

        return $this;
    }

    public function getDataZlozeniaDeklaracji(): ?\DateTimeInterface
    {
        return $this->dataZlozeniaDeklaracji;
    }

    public function setDataZlozeniaDeklaracji(?\DateTimeInterface $dataZlozeniaDeklaracji): self
    {
        $this->dataZlozeniaDeklaracji = $dataZlozeniaDeklaracji;

        return $this;
    }

    public function getNumerWPartii(): ?string
    {
        return $this->numerWPartii;
    }

    public function setNumerWPartii(?string $numerWPartii): self
    {
        $this->numerWPartii = $numerWPartii;

        return $this;
    }

    public function getDodatkoweInformacje(): ?string
    {
        return $this->dodatkoweInformacje;
    }

    public function setDodatkoweInformacje(?string $dodatkoweInformacje): self
    {
        $this->dodatkoweInformacje = $dodatkoweInformacje;

        return $this;
    }

    public function getNotatkaWewnetrzna(): ?string
    {
        return $this->notatkaWewnetrzna;
    }

    public function setNotatkaWewnetrzna(?string $notatkaWewnetrzna): self
    {
        $this->notatkaWewnetrzna = $notatkaWewnetrzna;

        return $this;
    }

    public function getOldId(): ?string
    {
        return $this->oldId;
    }

    public function setOldId(?string $oldId): self
    {
        $this->oldId = $oldId;

        return $this;
    }

    public function getPowodZakonczeniaCzlonkostwa(): ?string
    {
        return $this->powodZakonczeniaCzlonkostwa;
    }

    public function setPowodZakonczeniaCzlonkostwa(?string $powodZakonczeniaCzlonkostwa): self
    {
        $this->powodZakonczeniaCzlonkostwa = $powodZakonczeniaCzlonkostwa;

        return $this;
    }

    public function getDataZakonczeniaCzlonkostwa(): ?\DateTimeInterface
    {
        return $this->dataZakonczeniaCzlonkostwa;
    }

    public function setDataZakonczeniaCzlonkostwa(?\DateTimeInterface $dataZakonczeniaCzlonkostwa): self
    {
        $this->dataZakonczeniaCzlonkostwa = $dataZakonczeniaCzlonkostwa;

        return $this;
    }

    public function getOryginalnyIdCzlonka(): ?int
    {
        return $this->oryginalnyIdCzlonka;
    }

    public function setOryginalnyIdCzlonka(?int $oryginalnyIdCzlonka): self
    {
        $this->oryginalnyIdCzlonka = $oryginalnyIdCzlonka;

        return $this;
    }

    public function getFullName(): string
    {
        return trim(($this->imie ?? '').' '.($this->nazwisko ?? ''));
    }

    public function getDataPrzyjeciaDoPartii(): ?\DateTimeInterface
    {
        return $this->dataPrzyjecia;
    }

    public function getStatusSkladki(): array
    {
        // Byli członkowie nie płacą już składek - zawsze pokazujemy jako nieaktualną
        return [
            'status' => 'Nieaktualna',
            'class' => 'secondary',
            'icon' => 'fas fa-minus-circle'
        ];
    }
}
