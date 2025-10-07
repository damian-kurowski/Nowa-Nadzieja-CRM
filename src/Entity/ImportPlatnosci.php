<?php

namespace App\Entity;

use App\Repository\ImportPlatnosciRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImportPlatnosciRepository::class)]
class ImportPlatnosci
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nazwaPliku;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataImportu;

    #[ORM\Column]
    private int $liczbaDopasowanych = 0;

    #[ORM\Column]
    private int $liczbaBlednych = 0;

    #[ORM\Column]
    private int $liczbaWierszy = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $raportBledow = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $importowanyPrzez;

    public function __construct()
    {
        $this->dataImportu = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNazwaPliku(): string
    {
        return $this->nazwaPliku;
    }

    public function setNazwaPliku(string $nazwaPliku): self
    {
        $this->nazwaPliku = $nazwaPliku;
        return $this;
    }

    public function getDataImportu(): \DateTimeInterface
    {
        return $this->dataImportu;
    }

    public function setDataImportu(\DateTimeInterface $dataImportu): self
    {
        $this->dataImportu = $dataImportu;
        return $this;
    }

    public function getLiczbaDopasowanych(): int
    {
        return $this->liczbaDopasowanych;
    }

    public function setLiczbaDopasowanych(int $liczbaDopasowanych): self
    {
        $this->liczbaDopasowanych = $liczbaDopasowanych;
        return $this;
    }

    public function getLiczbaBlednych(): int
    {
        return $this->liczbaBlednych;
    }

    public function setLiczbaBlednych(int $liczbaBlednych): self
    {
        $this->liczbaBlednych = $liczbaBlednych;
        return $this;
    }

    public function getLiczbaWierszy(): int
    {
        return $this->liczbaWierszy;
    }

    public function setLiczbaWierszy(int $liczbaWierszy): self
    {
        $this->liczbaWierszy = $liczbaWierszy;
        return $this;
    }

    public function getRaportBledow(): ?string
    {
        return $this->raportBledow;
    }

    public function setRaportBledow(?string $raportBledow): self
    {
        $this->raportBledow = $raportBledow;
        return $this;
    }

    public function getImportowanyPrzez(): User
    {
        return $this->importowanyPrzez;
    }

    public function setImportowanyPrzez(User $importowanyPrzez): self
    {
        $this->importowanyPrzez = $importowanyPrzez;
        return $this;
    }

    public function getProcentDopasowań(): float
    {
        if ($this->liczbaWierszy === 0) {
            return 0;
        }
        
        return round(($this->liczbaDopasowanych / $this->liczbaWierszy) * 100, 2);
    }
}