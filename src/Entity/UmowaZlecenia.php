<?php

namespace App\Entity;

use App\Repository\UmowaZleceniaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UmowaZleceniaRepository::class)]
#[ORM\Table(name: 'umowa_zlecenia')]
#[ORM\HasLifecycleCallbacks]
class UmowaZlecenia
{
    public const STATUS_PROJEKT = 'projekt';
    public const STATUS_PODPISANA = 'podpisana';
    public const STATUS_ANULOWANA = 'anulowana';
    public const STATUS_ZAKONCZONA = 'zakonczona';

    public const ZAKRES_KAMPANIA = 'kampania';
    public const ZAKRES_GRAFIKA = 'grafika';
    public const ZAKRES_SOCIAL_MEDIA = 'social_media';
    public const ZAKRES_CONTENT = 'content';
    public const ZAKRES_ORGANIZACJA = 'organizacja';
    public const ZAKRES_INNE = 'inne';

    public const TYP_OKRESU_OD_DO = 'od_do';
    public const TYP_OKRESU_OD = 'od';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\Length(max: 100, maxMessage: 'Numer umowy nie może być dłuższy niż {{ limit }} znaków')]
    private ?string $numerUmowy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Osoba tworząca umowę jest wymagana')]
    private ?User $tworca = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $sekretarzPartii = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Zleceniobiorca jest wymagany')]
    private ?User $zleceniobiorca = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Zakres umowy jest wymagany')]
    #[Assert\Choice(
        choices: [
            self::ZAKRES_KAMPANIA,
            self::ZAKRES_GRAFIKA,
            self::ZAKRES_SOCIAL_MEDIA,
            self::ZAKRES_CONTENT,
            self::ZAKRES_ORGANIZACJA,
            self::ZAKRES_INNE
        ],
        message: 'Nieprawidłowy zakres umowy'
    )]
    private ?string $zakresUmowy = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Szczegółowy opis zakresu jest wymagany')]
    #[Assert\Length(
        min: 10,
        max: 2000,
        minMessage: 'Opis zakresu musi mieć co najmniej {{ limit }} znaków',
        maxMessage: 'Opis zakresu nie może być dłuższy niż {{ limit }} znaków'
    )]
    private ?string $opisZakresu = null;

    #[ORM\Column(length: 10)]
    #[Assert\NotBlank(message: 'Typ okresu jest wymagany')]
    #[Assert\Choice(
        choices: [self::TYP_OKRESU_OD_DO, self::TYP_OKRESU_OD],
        message: 'Nieprawidłowy typ okresu'
    )]
    private ?string $typOkresu = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'Data rozpoczęcia jest wymagana')]
    #[Assert\GreaterThanOrEqual('today', message: 'Data rozpoczęcia nie może być w przeszłości')]
    private ?\DateTimeInterface $dataOd = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataDo = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Wynagrodzenie jest wymagane')]
    #[Assert\Positive(message: 'Wynagrodzenie musi być większe od zera')]
    private ?string $wynagrodzenie = null;

    #[ORM\Column(length: 34, nullable: true)]
    #[Assert\Length(
        min: 26,
        max: 34,
        minMessage: 'Numer konta musi mieć co najmniej {{ limit }} znaków',
        maxMessage: 'Numer konta nie może być dłuższy niż {{ limit }} znaków'
    )]
    #[Assert\Regex(
        pattern: '/^[A-Z]{2}[0-9]{2}[\s0-9]*$/',
        message: 'Numer konta musi być w formacie IBAN'
    )]
    private ?string $numerKonta = null;

    #[ORM\Column]
    private ?bool $pobranieKontaZSkladek = false;

    #[ORM\Column]
    private ?bool $czyStudent = false;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(
        choices: [
            self::STATUS_PROJEKT,
            self::STATUS_PODPISANA,
            self::STATUS_ANULOWANA,
            self::STATUS_ZAKONCZONA
        ],
        message: 'Nieprawidłowy status umowy'
    )]
    private ?string $status = self::STATUS_PROJEKT;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dataUtworzenia = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPodpisania = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZakonczenia = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $uwagi = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $skanPodpisanejUmowy = null;

    public function __construct()
    {
        $this->dataUtworzenia = new \DateTime();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if ($this->dataUtworzenia === null) {
            $this->dataUtworzenia = new \DateTime();
        }
        
        if ($this->numerUmowy === null) {
            $this->numerUmowy = $this->generateUmowaNumber();
        }
    }

    private function generateUmowaNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $timestamp = time();
        
        return sprintf('UZ/%s/%s/%s', $year, $month, substr($timestamp, -4));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumerUmowy(): ?string
    {
        return $this->numerUmowy;
    }

    public function setNumerUmowy(string $numerUmowy): static
    {
        $this->numerUmowy = $numerUmowy;
        return $this;
    }

    public function getTworca(): ?User
    {
        return $this->tworca;
    }

    public function setTworca(?User $tworca): static
    {
        $this->tworca = $tworca;
        return $this;
    }

    public function getSekretarzPartii(): ?User
    {
        return $this->sekretarzPartii;
    }

    public function setSekretarzPartii(?User $sekretarzPartii): static
    {
        $this->sekretarzPartii = $sekretarzPartii;
        return $this;
    }

    public function getZleceniobiorca(): ?User
    {
        return $this->zleceniobiorca;
    }

    public function setZleceniobiorca(?User $zleceniobiorca): static
    {
        $this->zleceniobiorca = $zleceniobiorca;
        return $this;
    }

    public function getZakresUmowy(): ?string
    {
        return $this->zakresUmowy;
    }

    public function setZakresUmowy(string $zakresUmowy): static
    {
        $this->zakresUmowy = $zakresUmowy;
        return $this;
    }

    public function getOpisZakresu(): ?string
    {
        return $this->opisZakresu;
    }

    public function setOpisZakresu(string $opisZakresu): static
    {
        $this->opisZakresu = $opisZakresu;
        return $this;
    }

    public function getTypOkresu(): ?string
    {
        return $this->typOkresu;
    }

    public function setTypOkresu(string $typOkresu): static
    {
        $this->typOkresu = $typOkresu;
        return $this;
    }

    public function getDataOd(): ?\DateTimeInterface
    {
        return $this->dataOd;
    }

    public function setDataOd(\DateTimeInterface $dataOd): static
    {
        $this->dataOd = $dataOd;
        return $this;
    }

    public function getDataDo(): ?\DateTimeInterface
    {
        return $this->dataDo;
    }

    public function setDataDo(?\DateTimeInterface $dataDo): static
    {
        $this->dataDo = $dataDo;
        return $this;
    }

    public function getWynagrodzenie(): ?string
    {
        return $this->wynagrodzenie;
    }

    public function setWynagrodzenie(string $wynagrodzenie): static
    {
        $this->wynagrodzenie = $wynagrodzenie;
        return $this;
    }

    public function getNumerKonta(): ?string
    {
        return $this->numerKonta;
    }

    public function setNumerKonta(?string $numerKonta): static
    {
        $this->numerKonta = $numerKonta;
        return $this;
    }

    public function isPobranieKontaZSkladek(): ?bool
    {
        return $this->pobranieKontaZSkladek;
    }

    public function setPobranieKontaZSkladek(bool $pobranieKontaZSkladek): static
    {
        $this->pobranieKontaZSkladek = $pobranieKontaZSkladek;
        return $this;
    }

    public function isCzyStudent(): ?bool
    {
        return $this->czyStudent;
    }

    public function setCzyStudent(bool $czyStudent): static
    {
        $this->czyStudent = $czyStudent;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getDataUtworzenia(): ?\DateTimeInterface
    {
        return $this->dataUtworzenia;
    }

    public function setDataUtworzenia(\DateTimeInterface $dataUtworzenia): static
    {
        $this->dataUtworzenia = $dataUtworzenia;
        return $this;
    }

    public function getDataPodpisania(): ?\DateTimeInterface
    {
        return $this->dataPodpisania;
    }

    public function setDataPodpisania(?\DateTimeInterface $dataPodpisania): static
    {
        $this->dataPodpisania = $dataPodpisania;
        return $this;
    }

    public function getDataZakonczenia(): ?\DateTimeInterface
    {
        return $this->dataZakonczenia;
    }

    public function setDataZakonczenia(?\DateTimeInterface $dataZakonczenia): static
    {
        $this->dataZakonczenia = $dataZakonczenia;
        return $this;
    }

    public function getUwagi(): ?string
    {
        return $this->uwagi;
    }

    public function setUwagi(?string $uwagi): static
    {
        $this->uwagi = $uwagi;
        return $this;
    }

    // Helper methods
    public function isProjekt(): bool
    {
        return $this->status === self::STATUS_PROJEKT;
    }

    public function isPodpisana(): bool
    {
        return $this->status === self::STATUS_PODPISANA;
    }

    public function isAnulowana(): bool
    {
        return $this->status === self::STATUS_ANULOWANA;
    }

    public function isZakonczona(): bool
    {
        return $this->status === self::STATUS_ZAKONCZONA;
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PROJEKT => 'Projekt',
            self::STATUS_PODPISANA => 'Podpisana',
            self::STATUS_ANULOWANA => 'Anulowana',
            self::STATUS_ZAKONCZONA => 'Zakończona',
            default => 'Nieznany'
        };
    }

    public function getZakresLabel(): string
    {
        return match($this->zakresUmowy) {
            self::ZAKRES_KAMPANIA => 'Kampania wyborcza',
            self::ZAKRES_GRAFIKA => 'Projekty graficzne',
            self::ZAKRES_SOCIAL_MEDIA => 'Social media',
            self::ZAKRES_CONTENT => 'Tworzenie treści',
            self::ZAKRES_ORGANIZACJA => 'Organizacja wydarzeń',
            self::ZAKRES_INNE => 'Inne',
            default => 'Nieznany'
        };
    }

    public static function getStatusChoices(): array
    {
        return [
            'Projekt' => self::STATUS_PROJEKT,
            'Podpisana' => self::STATUS_PODPISANA,
            'Anulowana' => self::STATUS_ANULOWANA,
            'Zakończona' => self::STATUS_ZAKONCZONA,
        ];
    }

    public static function getZakresChoices(): array
    {
        return [
            'Kampania wyborcza' => self::ZAKRES_KAMPANIA,
            'Projekty graficzne' => self::ZAKRES_GRAFIKA,
            'Social media' => self::ZAKRES_SOCIAL_MEDIA,
            'Tworzenie treści' => self::ZAKRES_CONTENT,
            'Organizacja wydarzeń' => self::ZAKRES_ORGANIZACJA,
            'Inne' => self::ZAKRES_INNE,
        ];
    }

    public static function getTypOkresuChoices(): array
    {
        return [
            'Od - do (okres określony)' => self::TYP_OKRESU_OD_DO,
            'Od (okres nieokreślony)' => self::TYP_OKRESU_OD,
        ];
    }

    public function getFormattedWynagrodzenie(): string
    {
        return number_format((float)$this->wynagrodzenie, 2, ',', ' ') . ' zł';
    }

    public function getOkresDisplay(): string
    {
        if ($this->typOkresu === self::TYP_OKRESU_OD_DO && $this->dataDo) {
            return sprintf('od %s do %s', 
                $this->dataOd->format('d.m.Y'), 
                $this->dataDo->format('d.m.Y')
            );
        }
        
        return sprintf('od %s', $this->dataOd->format('d.m.Y'));
    }

    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_PROJEKT => 'secondary',
            self::STATUS_PODPISANA => 'success',
            self::STATUS_ANULOWANA => 'danger',
            self::STATUS_ZAKONCZONA => 'primary',
            default => 'dark'
        };
    }

    public function getSkanPodpisanejUmowy(): ?string
    {
        return $this->skanPodpisanejUmowy;
    }

    public function setSkanPodpisanejUmowy(?string $skanPodpisanejUmowy): static
    {
        $this->skanPodpisanejUmowy = $skanPodpisanejUmowy;
        return $this;
    }

    public function hasSkanPodpisanejUmowy(): bool
    {
        return !empty($this->skanPodpisanejUmowy);
    }
}