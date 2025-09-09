<?php

namespace App\Entity;

use App\Repository\SkladkaCzlonkowskaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SkladkaCzlonkowskaRepository::class)]
#[ORM\Index(name: 'idx_skladka_czlonek', columns: ['czlonek_id'])]
#[ORM\Index(name: 'idx_skladka_status', columns: ['status'])]
#[ORM\Index(name: 'idx_skladka_okres', columns: ['rok', 'miesiac'])]
#[ORM\Index(name: 'idx_skladka_data_platnosci', columns: ['data_platnosci'])]
#[ORM\Index(name: 'idx_skladka_czlonek_okres', columns: ['czlonek_id', 'rok', 'miesiac'])]
class SkladkaCzlonkowska
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'skladkiCzlonkowskie')]
    #[ORM\JoinColumn(nullable: false)]
    private User $czlonek;

    #[Assert\NotBlank(message: 'Rok jest wymagany')]
    #[Assert\Range(min: 2020, max: 2100, notInRangeMessage: 'Rok musi być między {{ min }} a {{ max }}')]
    #[ORM\Column]
    private int $rok;

    #[Assert\NotBlank(message: 'Miesiąc jest wymagany')]
    #[Assert\Range(min: 1, max: 12, notInRangeMessage: 'Miesiąc musi być między {{ min }} a {{ max }}')]
    #[ORM\Column]
    private int $miesiac;

    #[Assert\NotBlank(message: 'Kwota jest wymagana')]
    #[Assert\PositiveOrZero(message: 'Kwota musi być większa lub równa zero')]
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $kwota;

    #[ORM\Column(length: 50)]
    private string $status = 'oczekujaca'; // 'oczekujaca', 'oplacona', 'anulowana', 'zwolniona'

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataRejestracji;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPlatnosci = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataWaznosciSkladki = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numerPlatnosci = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $uwagi = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $sposobPlatnosci = null; // 'przelew', 'gotowka', 'karta', 'blik'

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $zarejestrowanePrzez = null;

    #[ORM\ManyToOne(targetEntity: ImportPlatnosci::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?ImportPlatnosci $importPlatnosci = null;

    public function __construct()
    {
        $this->dataRejestracji = new \DateTime();
        $this->status = 'oczekujaca';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCzlonek(): User
    {
        return $this->czlonek;
    }

    public function setCzlonek(User $czlonek): self
    {
        $this->czlonek = $czlonek;
        return $this;
    }

    public function getRok(): int
    {
        return $this->rok;
    }

    public function setRok(int $rok): self
    {
        $this->rok = $rok;
        return $this;
    }

    public function getMiesiac(): int
    {
        return $this->miesiac;
    }

    public function setMiesiac(int $miesiac): self
    {
        $this->miesiac = $miesiac;
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDataRejestracji(): \DateTimeInterface
    {
        return $this->dataRejestracji;
    }

    public function setDataRejestracji(\DateTimeInterface $dataRejestracji): self
    {
        $this->dataRejestracji = $dataRejestracji;
        return $this;
    }

    public function getDataPlatnosci(): ?\DateTimeInterface
    {
        return $this->dataPlatnosci;
    }

    public function setDataPlatnosci(?\DateTimeInterface $dataPlatnosci): self
    {
        $this->dataPlatnosci = $dataPlatnosci;
        return $this;
    }

    public function getDataWaznosciSkladki(): ?\DateTimeInterface
    {
        return $this->dataWaznosciSkladki;
    }

    public function setDataWaznosciSkladki(?\DateTimeInterface $dataWaznosciSkladki): self
    {
        $this->dataWaznosciSkladki = $dataWaznosciSkladki;
        return $this;
    }

    public function getNumerPlatnosci(): ?string
    {
        return $this->numerPlatnosci;
    }

    public function setNumerPlatnosci(?string $numerPlatnosci): self
    {
        $this->numerPlatnosci = $numerPlatnosci;
        return $this;
    }

    public function getUwagi(): ?string
    {
        return $this->uwagi;
    }

    public function setUwagi(?string $uwagi): self
    {
        $this->uwagi = $uwagi;
        return $this;
    }

    public function getSposobPlatnosci(): ?string
    {
        return $this->sposobPlatnosci;
    }

    public function setSposobPlatnosci(?string $sposobPlatnosci): self
    {
        $this->sposobPlatnosci = $sposobPlatnosci;
        return $this;
    }

    public function getZarejestrowanePrzez(): ?User
    {
        return $this->zarejestrowanePrzez;
    }

    public function setZarejestrowanePrzez(?User $zarejestrowanePrzez): self
    {
        $this->zarejestrowanePrzez = $zarejestrowanePrzez;
        return $this;
    }

    public function getImportPlatnosci(): ?ImportPlatnosci
    {
        return $this->importPlatnosci;
    }

    public function setImportPlatnosci(?ImportPlatnosci $importPlatnosci): self
    {
        $this->importPlatnosci = $importPlatnosci;
        return $this;
    }

    public function getNazwaMiesiaca(): string
    {
        $miesiace = [
            1 => 'Styczeń', 2 => 'Luty', 3 => 'Marzec', 4 => 'Kwiecień',
            5 => 'Maj', 6 => 'Czerwiec', 7 => 'Lipiec', 8 => 'Sierpień',
            9 => 'Wrzesień', 10 => 'Październik', 11 => 'Listopad', 12 => 'Grudzień'
        ];
        
        return $miesiace[$this->miesiac] ?? 'Nieznany';
    }

    public function getOkresOplaty(): string
    {
        return $this->getNazwaMiesiaca() . ' ' . $this->rok;
    }

    public function isOplacona(): bool
    {
        return $this->status === 'oplacona';
    }

    public function isWazna(): bool
    {
        if (!$this->isOplacona() || !$this->dataWaznosciSkladki) {
            return false;
        }
        
        return $this->dataWaznosciSkladki >= new \DateTime();
    }

    public function __toString(): string
    {
        return sprintf(
            'Składka %s - %s (%s)',
            $this->getOkresOplaty(),
            $this->czlonek->getImieNazwisko(),
            $this->status
        );
    }
}