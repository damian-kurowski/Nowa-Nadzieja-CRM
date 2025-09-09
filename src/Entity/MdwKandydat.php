<?php

namespace App\Entity;

use App\Repository\MdwKandydatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MdwKandydatRepository::class)]
#[ORM\Table(name: 'mdw_kandydaci')]
class MdwKandydat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $imie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $drugieImie = null;

    #[ORM\Column(length: 255)]
    private ?string $nazwisko = null;

    #[ORM\Column(length: 11)]
    private ?string $pesel = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $adresZamieszkania = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dataZlozeniaDeklaracji = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 15)]
    private ?string $telefon = null;

    #[ORM\ManyToOne(targetEntity: Region::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Region $region = null;

    #[ORM\ManyToOne(targetEntity: Okreg::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Okreg $okreg = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImie(): ?string
    {
        return $this->imie;
    }

    public function setImie(string $imie): static
    {
        $this->imie = $imie;

        return $this;
    }

    public function getDrugieImie(): ?string
    {
        return $this->drugieImie;
    }

    public function setDrugieImie(?string $drugieImie): static
    {
        $this->drugieImie = $drugieImie;

        return $this;
    }

    public function getNazwisko(): ?string
    {
        return $this->nazwisko;
    }

    public function setNazwisko(string $nazwisko): static
    {
        $this->nazwisko = $nazwisko;

        return $this;
    }

    public function getPesel(): ?string
    {
        return $this->pesel;
    }

    public function setPesel(string $pesel): static
    {
        $this->pesel = $pesel;

        return $this;
    }

    public function getAdresZamieszkania(): ?string
    {
        return $this->adresZamieszkania;
    }

    public function setAdresZamieszkania(string $adresZamieszkania): static
    {
        $this->adresZamieszkania = $adresZamieszkania;

        return $this;
    }

    public function getDataZlozeniaDeklaracji(): ?\DateTimeInterface
    {
        return $this->dataZlozeniaDeklaracji;
    }

    public function setDataZlozeniaDeklaracji(\DateTimeInterface $dataZlozeniaDeklaracji): static
    {
        $this->dataZlozeniaDeklaracji = $dataZlozeniaDeklaracji;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getTelefon(): ?string
    {
        return $this->telefon;
    }

    public function setTelefon(string $telefon): static
    {
        $this->telefon = $telefon;

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): static
    {
        $this->region = $region;

        return $this;
    }

    public function getOkreg(): ?Okreg
    {
        return $this->okreg;
    }

    public function setOkreg(?Okreg $okreg): static
    {
        $this->okreg = $okreg;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFullName(): string
    {
        $name = $this->imie;
        if ($this->drugieImie) {
            $name .= ' ' . $this->drugieImie;
        }
        $name .= ' ' . $this->nazwisko;
        
        return $name;
    }
}