<?php

namespace App\Entity;

use App\Repository\PostepKandydataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostepKandydataRepository::class)]
class PostepKandydata
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: 'App\Entity\User', inversedBy: 'postepKandydataEntity')]
    #[ORM\JoinColumn(nullable: false)]
    private User $kandydat;

    // Krok 1: Opłacenie składki
    #[ORM\Column]
    private bool $krok1OplacenieSkladki = false;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private ?User $krok1OdznaczylUzytkownik = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $krok1DataOdznaczenia = null;

    // Krok 2: Wgranie zdjęcia
    #[ORM\Column]
    private bool $krok2WgranieZdjecia = false;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private ?User $krok2OdznaczylUzytkownik = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $krok2DataOdznaczenia = null;

    // Krok 3: Wgranie CV
    #[ORM\Column]
    private bool $krok3WgranieCv = false;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private ?User $krok3OdznaczylUzytkownik = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $krok3DataOdznaczenia = null;

    // Krok 4: Uzupełnienie profilu
    #[ORM\Column]
    private bool $krok4UzupelnienieProfilu = false;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private ?User $krok4OdznaczylUzytkownik = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $krok4DataOdznaczenia = null;

    // Krok 5: Rozmowa prekwalifikacyjna
    #[ORM\Column]
    private bool $krok5RozmowaPrekwalifikacyjna = false;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private ?User $krok5OdznaczylUzytkownik = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $krok5DataOdznaczenia = null;

    // Krok 6: Opinia Rady oddziału
    #[ORM\Column]
    private bool $krok6OpiniaRadyOddzialu = false;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private ?User $krok6OdznaczylUzytkownik = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $krok6DataOdznaczenia = null;

    // Krok 7: Udział w zebraniach przez 3 miesiące
    #[ORM\Column]
    private bool $krok7UdzialWZebraniach = false;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private ?User $krok7OdznaczylUzytkownik = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $krok7DataOdznaczenia = null;

    // Krok 8: Decyzja
    #[ORM\Column]
    private bool $krok8Decyzja = false;
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private ?User $krok8OdznaczylUzytkownik = null;
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $krok8DataOdznaczenia = null;
    
    // Stare pola - do usunięcia po migracji
    #[ORM\Column]
    private bool $skladkaZa3Miesiace = false;
    #[ORM\Column]
    private bool $dodanieCvWMiesiacu = false;
    #[ORM\Column]
    private bool $spotkanieMiesieczne = false;
    #[ORM\Column]
    private bool $zaangazowanieWDzialanie = false;
    #[ORM\Column]
    private bool $rozmowaZPrezesem = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $decyzjaPrezesa = null; // 'akceptacja', 'odrzucenie', 'oczekiwanie'

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataDecyzji = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $uwagi = null;

    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    private ?User $prezesOdpowiedzialny = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dataRozpoczecia = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZakonczenia = null;

    #[ORM\Column]
    private int $aktualnyEtap = 1;

    public function __construct()
    {
        $this->dataRozpoczecia = new \DateTime();
        $this->aktualnyEtap = 1;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKandydat(): User
    {
        return $this->kandydat;
    }

    public function setKandydat(User $kandydat): self
    {
        $this->kandydat = $kandydat;

        return $this;
    }

    public function isSkladkaZa3Miesiace(): bool
    {
        return $this->skladkaZa3Miesiace;
    }

    public function setSkladkaZa3Miesiace(bool $skladkaZa3Miesiace): self
    {
        $this->skladkaZa3Miesiace = $skladkaZa3Miesiace;

        return $this;
    }

    public function isDodanieCvWMiesiacu(): bool
    {
        return $this->dodanieCvWMiesiacu;
    }

    public function setDodanieCvWMiesiacu(bool $dodanieCvWMiesiacu): self
    {
        $this->dodanieCvWMiesiacu = $dodanieCvWMiesiacu;

        return $this;
    }

    public function isSpotkanieMiesieczne(): bool
    {
        return $this->spotkanieMiesieczne;
    }

    public function setSpotkanieMiesieczne(bool $spotkanieMiesieczne): self
    {
        $this->spotkanieMiesieczne = $spotkanieMiesieczne;

        return $this;
    }

    public function isZaangaowanieWDzialanie(): bool
    {
        return $this->zaangazowanieWDzialanie;
    }

    public function setZaangazowanieWDzialanie(bool $zaangazowanieWDzialanie): self
    {
        $this->zaangazowanieWDzialanie = $zaangazowanieWDzialanie;

        return $this;
    }

    public function isRozmowaZPrezesem(): bool
    {
        return $this->rozmowaZPrezesem;
    }

    public function setRozmowaZPrezesem(bool $rozmowaZPrezesem): self
    {
        $this->rozmowaZPrezesem = $rozmowaZPrezesem;

        return $this;
    }

    public function getDecyzjaPrezesa(): ?string
    {
        return $this->decyzjaPrezesa;
    }

    public function setDecyzjaPrezesa(?string $decyzjaPrezesa): self
    {
        $this->decyzjaPrezesa = $decyzjaPrezesa;

        return $this;
    }

    public function getDataDecyzji(): ?\DateTimeInterface
    {
        return $this->dataDecyzji;
    }

    public function setDataDecyzji(?\DateTimeInterface $dataDecyzji): self
    {
        $this->dataDecyzji = $dataDecyzji;

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

    public function getPrezesOdpowiedzialny(): ?User
    {
        return $this->prezesOdpowiedzialny;
    }

    public function setPrezesOdpowiedzialny(?User $prezesOdpowiedzialny): self
    {
        $this->prezesOdpowiedzialny = $prezesOdpowiedzialny;

        return $this;
    }

    public function getDataRozpoczecia(): ?\DateTimeInterface
    {
        return $this->dataRozpoczecia;
    }

    public function setDataRozpoczecia(?\DateTimeInterface $dataRozpoczecia): self
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

    public function getAktualnyEtap(): int
    {
        return $this->aktualnyEtap;
    }

    public function setAktualnyEtap(int $aktualnyEtap): self
    {
        $this->aktualnyEtap = $aktualnyEtap;
        return $this;
    }

    public function getPostepProcentowy(): int
    {
        $kroki = [
            $this->krok1OplacenieSkladki,
            $this->krok2WgranieZdjecia,
            $this->krok3WgranieCv,
            $this->krok4UzupelnienieProfilu,
            $this->krok5RozmowaPrekwalifikacyjna,
            $this->krok6OpiniaRadyOddzialu,
            $this->krok7UdzialWZebraniach,
            $this->krok8Decyzja,
        ];

        $ukonczone = count(array_filter($kroki));

        return intval(($ukonczone / count($kroki)) * 100);
    }
    
    public function getKrokiPostepuArray(): array
    {
        return [
            1 => [
                'nazwa' => 'Opłacenie składki',
                'wykonane' => $this->krok1OplacenieSkladki,
                'odznaczyl' => $this->krok1OdznaczylUzytkownik,
                'data' => $this->krok1DataOdznaczenia,
            ],
            2 => [
                'nazwa' => 'Wgranie zdjęcia',
                'wykonane' => $this->krok2WgranieZdjecia,
                'odznaczyl' => $this->krok2OdznaczylUzytkownik,
                'data' => $this->krok2DataOdznaczenia,
            ],
            3 => [
                'nazwa' => 'Wgranie CV',
                'wykonane' => $this->krok3WgranieCv,
                'odznaczyl' => $this->krok3OdznaczylUzytkownik,
                'data' => $this->krok3DataOdznaczenia,
            ],
            4 => [
                'nazwa' => 'Uzupełnienie profilu',
                'wykonane' => $this->krok4UzupelnienieProfilu,
                'odznaczyl' => $this->krok4OdznaczylUzytkownik,
                'data' => $this->krok4DataOdznaczenia,
            ],
            5 => [
                'nazwa' => 'Rozmowa prekwalifikacyjna',
                'wykonane' => $this->krok5RozmowaPrekwalifikacyjna,
                'odznaczyl' => $this->krok5OdznaczylUzytkownik,
                'data' => $this->krok5DataOdznaczenia,
            ],
            6 => [
                'nazwa' => 'Opinia Rady oddziału',
                'wykonane' => $this->krok6OpiniaRadyOddzialu,
                'odznaczyl' => $this->krok6OdznaczylUzytkownik,
                'data' => $this->krok6DataOdznaczenia,
            ],
            7 => [
                'nazwa' => 'Udział w zebraniach przez 3 miesiące',
                'wykonane' => $this->krok7UdzialWZebraniach,
                'odznaczyl' => $this->krok7OdznaczylUzytkownik,
                'data' => $this->krok7DataOdznaczenia,
            ],
            8 => [
                'nazwa' => 'Decyzja',
                'wykonane' => $this->krok8Decyzja,
                'odznaczyl' => $this->krok8OdznaczylUzytkownik,
                'data' => $this->krok8DataOdznaczenia,
            ],
        ];
    }
    
    public function odznaczKrok(int $numerKroku, User $uzytkownik): void
    {
        $teraz = new \DateTime();
        
        switch ($numerKroku) {
            case 1:
                $this->krok1OplacenieSkladki = true;
                $this->krok1OdznaczylUzytkownik = $uzytkownik;
                $this->krok1DataOdznaczenia = $teraz;
                break;
            case 2:
                $this->krok2WgranieZdjecia = true;
                $this->krok2OdznaczylUzytkownik = $uzytkownik;
                $this->krok2DataOdznaczenia = $teraz;
                break;
            case 3:
                $this->krok3WgranieCv = true;
                $this->krok3OdznaczylUzytkownik = $uzytkownik;
                $this->krok3DataOdznaczenia = $teraz;
                break;
            case 4:
                $this->krok4UzupelnienieProfilu = true;
                $this->krok4OdznaczylUzytkownik = $uzytkownik;
                $this->krok4DataOdznaczenia = $teraz;
                break;
            case 5:
                $this->krok5RozmowaPrekwalifikacyjna = true;
                $this->krok5OdznaczylUzytkownik = $uzytkownik;
                $this->krok5DataOdznaczenia = $teraz;
                break;
            case 6:
                $this->krok6OpiniaRadyOddzialu = true;
                $this->krok6OdznaczylUzytkownik = $uzytkownik;
                $this->krok6DataOdznaczenia = $teraz;
                break;
            case 7:
                $this->krok7UdzialWZebraniach = true;
                $this->krok7OdznaczylUzytkownik = $uzytkownik;
                $this->krok7DataOdznaczenia = $teraz;
                break;
            case 8:
                $this->krok8Decyzja = true;
                $this->krok8OdznaczylUzytkownik = $uzytkownik;
                $this->krok8DataOdznaczenia = $teraz;
                break;
        }
        
        // Automatycznie uaktualnij aktualny etap
        $this->aktualnyEtap = $this->getOstatniUkonczonyEtap() + 1;
        if ($this->aktualnyEtap > 8) {
            $this->aktualnyEtap = 8;
            if ($this->krok8Decyzja && !$this->dataZakonczenia) {
                $this->dataZakonczenia = $teraz;
            }
        }
    }
    
    public function odznaczKrokWstecz(int $numerKroku): void
    {
        switch ($numerKroku) {
            case 1:
                $this->krok1OplacenieSkladki = false;
                $this->krok1OdznaczylUzytkownik = null;
                $this->krok1DataOdznaczenia = null;
                break;
            case 2:
                $this->krok2WgranieZdjecia = false;
                $this->krok2OdznaczylUzytkownik = null;
                $this->krok2DataOdznaczenia = null;
                break;
            case 3:
                $this->krok3WgranieCv = false;
                $this->krok3OdznaczylUzytkownik = null;
                $this->krok3DataOdznaczenia = null;
                break;
            case 4:
                $this->krok4UzupelnienieProfilu = false;
                $this->krok4OdznaczylUzytkownik = null;
                $this->krok4DataOdznaczenia = null;
                break;
            case 5:
                $this->krok5RozmowaPrekwalifikacyjna = false;
                $this->krok5OdznaczylUzytkownik = null;
                $this->krok5DataOdznaczenia = null;
                break;
            case 6:
                $this->krok6OpiniaRadyOddzialu = false;
                $this->krok6OdznaczylUzytkownik = null;
                $this->krok6DataOdznaczenia = null;
                break;
            case 7:
                $this->krok7UdzialWZebraniach = false;
                $this->krok7OdznaczylUzytkownik = null;
                $this->krok7DataOdznaczenia = null;
                break;
            case 8:
                $this->krok8Decyzja = false;
                $this->krok8OdznaczylUzytkownik = null;
                $this->krok8DataOdznaczenia = null;
                $this->dataZakonczenia = null;
                break;
        }
        
        // Uaktualnij aktualny etap
        $this->aktualnyEtap = $this->getOstatniUkonczonyEtap() + 1;
        if ($this->aktualnyEtap < 1) {
            $this->aktualnyEtap = 1;
        }
    }
    
    private function getOstatniUkonczonyEtap(): int
    {
        $kroki = [
            1 => $this->krok1OplacenieSkladki,
            2 => $this->krok2WgranieZdjecia,
            3 => $this->krok3WgranieCv,
            4 => $this->krok4UzupelnienieProfilu,
            5 => $this->krok5RozmowaPrekwalifikacyjna,
            6 => $this->krok6OpiniaRadyOddzialu,
            7 => $this->krok7UdzialWZebraniach,
            8 => $this->krok8Decyzja,
        ];
        
        $ostatniUkończony = 0;
        for ($i = 1; $i <= 8; $i++) {
            if ($kroki[$i]) {
                $ostatniUkończony = $i;
            }
        }
        
        return $ostatniUkończony;
    }
    
    // Gettery i settery dla nowych pól
    public function isKrok1OplacenieSkladki(): bool { return $this->krok1OplacenieSkladki; }
    public function setKrok1OplacenieSkladki(bool $value): self { $this->krok1OplacenieSkladki = $value; return $this; }
    public function getKrok1OdznaczylUzytkownik(): ?User { return $this->krok1OdznaczylUzytkownik; }
    public function setKrok1OdznaczylUzytkownik(?User $user): self { $this->krok1OdznaczylUzytkownik = $user; return $this; }
    public function getKrok1DataOdznaczenia(): ?\DateTimeInterface { return $this->krok1DataOdznaczenia; }
    public function setKrok1DataOdznaczenia(?\DateTimeInterface $date): self { $this->krok1DataOdznaczenia = $date; return $this; }
    
    public function isKrok2WgranieZdjecia(): bool { return $this->krok2WgranieZdjecia; }
    public function setKrok2WgranieZdjecia(bool $value): self { $this->krok2WgranieZdjecia = $value; return $this; }
    public function getKrok2OdznaczylUzytkownik(): ?User { return $this->krok2OdznaczylUzytkownik; }
    public function setKrok2OdznaczylUzytkownik(?User $user): self { $this->krok2OdznaczylUzytkownik = $user; return $this; }
    public function getKrok2DataOdznaczenia(): ?\DateTimeInterface { return $this->krok2DataOdznaczenia; }
    public function setKrok2DataOdznaczenia(?\DateTimeInterface $date): self { $this->krok2DataOdznaczenia = $date; return $this; }
    
    public function isKrok3WgranieCv(): bool { return $this->krok3WgranieCv; }
    public function setKrok3WgranieCv(bool $value): self { $this->krok3WgranieCv = $value; return $this; }
    public function getKrok3OdznaczylUzytkownik(): ?User { return $this->krok3OdznaczylUzytkownik; }
    public function setKrok3OdznaczylUzytkownik(?User $user): self { $this->krok3OdznaczylUzytkownik = $user; return $this; }
    public function getKrok3DataOdznaczenia(): ?\DateTimeInterface { return $this->krok3DataOdznaczenia; }
    public function setKrok3DataOdznaczenia(?\DateTimeInterface $date): self { $this->krok3DataOdznaczenia = $date; return $this; }
    
    public function isKrok4UzupelnienieProfilu(): bool { return $this->krok4UzupelnienieProfilu; }
    public function setKrok4UzupelnienieProfilu(bool $value): self { $this->krok4UzupelnienieProfilu = $value; return $this; }
    public function getKrok4OdznaczylUzytkownik(): ?User { return $this->krok4OdznaczylUzytkownik; }
    public function setKrok4OdznaczylUzytkownik(?User $user): self { $this->krok4OdznaczylUzytkownik = $user; return $this; }
    public function getKrok4DataOdznaczenia(): ?\DateTimeInterface { return $this->krok4DataOdznaczenia; }
    public function setKrok4DataOdznaczenia(?\DateTimeInterface $date): self { $this->krok4DataOdznaczenia = $date; return $this; }
    
    public function isKrok5RozmowaPrekwalifikacyjna(): bool { return $this->krok5RozmowaPrekwalifikacyjna; }
    public function setKrok5RozmowaPrekwalifikacyjna(bool $value): self { $this->krok5RozmowaPrekwalifikacyjna = $value; return $this; }
    public function getKrok5OdznaczylUzytkownik(): ?User { return $this->krok5OdznaczylUzytkownik; }
    public function setKrok5OdznaczylUzytkownik(?User $user): self { $this->krok5OdznaczylUzytkownik = $user; return $this; }
    public function getKrok5DataOdznaczenia(): ?\DateTimeInterface { return $this->krok5DataOdznaczenia; }
    public function setKrok5DataOdznaczenia(?\DateTimeInterface $date): self { $this->krok5DataOdznaczenia = $date; return $this; }
    
    public function isKrok6OpiniaRadyOddzialu(): bool { return $this->krok6OpiniaRadyOddzialu; }
    public function setKrok6OpiniaRadyOddzialu(bool $value): self { $this->krok6OpiniaRadyOddzialu = $value; return $this; }
    public function getKrok6OdznaczylUzytkownik(): ?User { return $this->krok6OdznaczylUzytkownik; }
    public function setKrok6OdznaczylUzytkownik(?User $user): self { $this->krok6OdznaczylUzytkownik = $user; return $this; }
    public function getKrok6DataOdznaczenia(): ?\DateTimeInterface { return $this->krok6DataOdznaczenia; }
    public function setKrok6DataOdznaczenia(?\DateTimeInterface $date): self { $this->krok6DataOdznaczenia = $date; return $this; }
    
    public function isKrok7UdzialWZebraniach(): bool { return $this->krok7UdzialWZebraniach; }
    public function setKrok7UdzialWZebraniach(bool $value): self { $this->krok7UdzialWZebraniach = $value; return $this; }
    public function getKrok7OdznaczylUzytkownik(): ?User { return $this->krok7OdznaczylUzytkownik; }
    public function setKrok7OdznaczylUzytkownik(?User $user): self { $this->krok7OdznaczylUzytkownik = $user; return $this; }
    public function getKrok7DataOdznaczenia(): ?\DateTimeInterface { return $this->krok7DataOdznaczenia; }
    public function setKrok7DataOdznaczenia(?\DateTimeInterface $date): self { $this->krok7DataOdznaczenia = $date; return $this; }
    
    public function isKrok8Decyzja(): bool { return $this->krok8Decyzja; }
    public function setKrok8Decyzja(bool $value): self { $this->krok8Decyzja = $value; return $this; }
    public function getKrok8OdznaczylUzytkownik(): ?User { return $this->krok8OdznaczylUzytkownik; }
    public function setKrok8OdznaczylUzytkownik(?User $user): self { $this->krok8OdznaczylUzytkownik = $user; return $this; }
    public function getKrok8DataOdznaczenia(): ?\DateTimeInterface { return $this->krok8DataOdznaczenia; }
    public function setKrok8DataOdznaczenia(?\DateTimeInterface $date): self { $this->krok8DataOdznaczenia = $date; return $this; }
}
