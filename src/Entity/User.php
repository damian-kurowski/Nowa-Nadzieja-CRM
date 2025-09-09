<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\Index(name: 'idx_user_typ_uzytkownika', columns: ['typ_uzytkownika'])]
#[ORM\Index(name: 'idx_user_status', columns: ['status'])]
#[ORM\Index(name: 'idx_user_okreg', columns: ['okreg_id'])]
#[ORM\Index(name: 'idx_user_oddzial', columns: ['oddzial_id'])]
#[ORM\Index(name: 'idx_user_typ_status', columns: ['typ_uzytkownika', 'status'])]
#[ORM\Index(name: 'idx_user_pesel', columns: ['pesel'])]
#[ORM\Index(name: 'idx_user_data_rejestracji', columns: ['data_rejestracji'])]
#[ORM\Index(name: 'idx_user_search', columns: ['imie', 'nazwisko', 'email'])]
#[UniqueEntity(fields: ['email'], message: 'Użytkownik z tym adresem email już istnieje.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\Email(message: 'Nieprawidłowy format email')]
    #[Assert\Length(max: 180, maxMessage: 'Email nie może być dłuższy niż {{ limit }} znaków')]
    #[Assert\NotBlank(message: 'Email jest wymagany')]
    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    /**
     * @var string[]
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    #[Assert\NotBlank(message: 'Imię jest wymagane')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Imię musi mieć co najmniej {{ limit }} znaki', maxMessage: 'Imię nie może być dłuższe niż {{ limit }} znaków')]
    #[Assert\Regex(pattern: '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ\s-]+$/', message: 'Imię może zawierać tylko litery, spacje i myślniki')]
    #[ORM\Column(length: 255)]
    private string $imie;

    #[Assert\Length(max: 255, maxMessage: 'Drugie imię nie może być dłuższe niż {{ limit }} znaków')]
    #[Assert\Regex(pattern: '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ\s-]*$/', message: 'Drugie imię może zawierać tylko litery, spacje i myślniki')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $drugieImie = null;

    #[Assert\NotBlank(message: 'Nazwisko jest wymagane')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Nazwisko musi mieć co najmniej {{ limit }} znaki', maxMessage: 'Nazwisko nie może być dłuższe niż {{ limit }} znaków')]
    #[Assert\Regex(pattern: '/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ\s-]+$/', message: 'Nazwisko może zawierać tylko litery, spacje i myślniki')]
    #[ORM\Column(length: 255)]
    private string $nazwisko;

    #[Assert\Regex(pattern: '/^\d{11}$/', message: 'PESEL musi składać się z 11 cyfr')]
    #[ORM\Column(length: 11, nullable: true)]
    private ?string $pesel = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataUrodzenia = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $plec = null;

    #[Assert\Regex(pattern: '/^(\+?48)?[1-9]\d{8}$/', message: 'Nieprawidłowy format numeru telefonu')]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $telefon = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresZamieszkania = null;
    
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresKorespondencyjny = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $socialMedia = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $informacjeOmnie = null;

    #[ORM\Column(name: 'zatrudnienie_spolki', type: Types::TEXT, nullable: true)]
    private ?string $zatrudnienieSpolki = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $zdjecie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\ManyToOne(targetEntity: Okreg::class, inversedBy: 'czlonkowie')]
    private ?Okreg $okreg = null;

    #[ORM\ManyToOne(targetEntity: Region::class, inversedBy: 'prezesi')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Region $region = null;

    #[ORM\ManyToOne(targetEntity: Oddzial::class, inversedBy: 'czlonkowie')]
    private ?Oddzial $oddzial = null;

    #[ORM\Column(length: 50)]
    private string $typUzytkownika; // 'czlonek', 'kandydat'

    #[ORM\Column(length: 50)]
    private string $status; // 'aktywny', 'nieaktywny', 'zawieszony'

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataRejestracji;

    #[ORM\Column(name: 'data_przyjecia_do_partii', type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPrzyjeciaDoPartii = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZlozeniaDeklaracji = null;

    #[ORM\Column(name: 'numer_w_partii', length: 255, nullable: true)]
    private ?string $numerWPartii = null;

    #[ORM\Column(name: 'numer_konta_bankowego', length: 34, nullable: true)]
    private ?string $numerKontaBankowego = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $dodatkoweInformacje = null;

    #[ORM\Column(name: 'notatka_wewnetrzna', type: Types::TEXT, nullable: true)]
    private ?string $notatkaWewnetrzna = null;

    // Pola dla śledzenia postępu kandydata (6-etapowy proces)
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataWypelnienieFormularza = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataWeryfikacjaDokumentow = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataRozmowaPrekwalifikacyjna = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataOpiniaRadyOddzialu = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataDecyzjaZarzadu = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataPrzyjecieUroczyste = null;

    /**
     * @var Collection<int, Funkcja>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Funkcja::class)]
    private Collection $funkcje;

    /**
     * @var Collection<int, OpiniaRadyOddzialu>
     */
    #[ORM\OneToMany(mappedBy: 'czlonek', targetEntity: OpiniaRadyOddzialu::class)]
    private Collection $opinie;

    /**
     * @var Collection<int, Platnosc>
     */
    #[ORM\OneToMany(mappedBy: 'darczyca', targetEntity: Platnosc::class)]
    private Collection $platnosci;

    /**
     * @var Collection<int, SkladkaCzlonkowska>
     */
    #[ORM\OneToMany(mappedBy: 'czlonek', targetEntity: SkladkaCzlonkowska::class)]
    private Collection $skladkiCzlonkowskie;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $skladkaOplacona = null;

    #[ORM\Column(name: 'data_oplacenia_skladki', type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataOplaceniaSkladki = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $kwotaSkladki = null;  // 20.00 PLN

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataWaznosciSkladki = null;

    // Relacja do postępu kandydata (nowy system 8 kroków)
    #[ORM\OneToOne(mappedBy: 'kandydat', targetEntity: PostepKandydata::class, cascade: ['persist', 'remove'])]
    private ?PostepKandydata $postepKandydataEntity = null;

    // Zatrudnienie w spółkach
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $zatrudnienieSpolkiMiejskie = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $zatrudnienieSpolkiSkarbuPanstwa = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $zatrudnienieSpolkiKomunalne = null;

    // Historia startów w wyborach
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $historiaWyborow = null;

    // Pełnione funkcje publiczne
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $funkcjePubliczne = null;

    // Przynależność do organizacji, stowarzyszeń i partii
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $przynaleznosc = null;

    // Zgoda RODO
    #[ORM\Column(type: Types::BOOLEAN, nullable: true)]
    private ?bool $zgodaRodo = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZgodyRodo = null;

    // Media społecznościowe - JSON z linkami
    /**
     * @var array<string, string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $mediaSpolecznosciowe = null;

    // Pola dla byłych członków
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $oldId = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $czyBylyCzlonek = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $powodZakonczeniaCzlonkostwa = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dataZakonczeniaCzlonkostwa = null;

    public function __construct()
    {
        $this->funkcje = new ArrayCollection();
        $this->opinie = new ArrayCollection();
        $this->platnosci = new ArrayCollection();
        $this->skladkiCzlonkowskie = new ArrayCollection();
        $this->dataRejestracji = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    /**
     * Zwraca identyfikator użytkownika do logowania (Symfony 6/7).
     */
    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    #[\Override]
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = array_values(array_unique($roles));

        return $this;
    }

    #[\Override]
    public function getPassword(): string
    {
        // PasswordAuthenticatedUserInterface wymaga stringa.
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    #[\Override]
    public function eraseCredentials(): void
    {
        // np. $this->plainPassword = null;
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
    
    public function getAdresKorespondencyjny(): ?string
    {
        return $this->adresKorespondencyjny;
    }
    
    public function setAdresKorespondencyjny(?string $adresKorespondencyjny): self
    {
        $this->adresKorespondencyjny = $adresKorespondencyjny;
        
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

    public function getInformacjeOmnie(): ?string
    {
        return $this->informacjeOmnie;
    }

    public function setInformacjeOmnie(?string $informacjeOmnie): self
    {
        $this->informacjeOmnie = $informacjeOmnie;

        return $this;
    }

    /**
     * Uwaga: pole zostało zmienione z $zatrudnienieSpółki na $zatrudnienieSpolki (usunięcie znaków diakrytycznych).
     * Metody zostawiam w ASCII.
     */
    public function getZatrudnienieSpolki(): ?string
    {
        return $this->zatrudnienieSpolki;
    }

    public function setZatrudnienieSpolki(?string $zatrudnienieSpolki): self
    {
        $this->zatrudnienieSpolki = $zatrudnienieSpolki;

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
        return $this->okreg;
    }

    public function setOkreg(?Okreg $okreg): self
    {
        $this->okreg = $okreg;

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): self
    {
        $this->region = $region;

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

    public function getTypUzytkownika(): string
    {
        return $this->typUzytkownika;
    }

    public function setTypUzytkownika(string $typUzytkownika): self
    {
        $this->typUzytkownika = $typUzytkownika;

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

    public function getDataPrzyjeciaDoPartii(): ?\DateTimeInterface
    {
        return $this->dataPrzyjeciaDoPartii;
    }

    public function setDataPrzyjeciaDoPartii(?\DateTimeInterface $dataPrzyjeciaDoPartii): self
    {
        $this->dataPrzyjeciaDoPartii = $dataPrzyjeciaDoPartii;

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

    /**
     * Relacje kolekcji: Funkcja.
     */
    /**
     * @return Collection<int, Funkcja>
     */
    public function getFunkcje(): Collection
    {
        return $this->funkcje;
    }

    public function addFunkcja(Funkcja $funkcja): self
    {
        if (!$this->funkcje->contains($funkcja)) {
            $this->funkcje->add($funkcja);
            $funkcja->setUser($this);
        }

        return $this;
    }

    public function removeFunkcja(Funkcja $funkcja): self
    {
        if ($this->funkcje->removeElement($funkcja)) {
            if ($funkcja->getUser() === $this) {
                // Don't set user to null since it might not be nullable
                // The relationship will be handled by Doctrine
            }
        }

        return $this;
    }

    /**
     * Relacje kolekcji: OpiniaRadyOddzialu (mappedBy="czlonek").
     */
    /**
     * @return Collection<int, OpiniaRadyOddzialu>
     */
    public function getOpinie(): Collection
    {
        return $this->opinie;
    }

    public function addOpinia(OpiniaRadyOddzialu $opinia): self
    {
        if (!$this->opinie->contains($opinia)) {
            $this->opinie->add($opinia);
            $opinia->setCzlonek($this);
        }

        return $this;
    }

    public function removeOpinia(OpiniaRadyOddzialu $opinia): self
    {
        if ($this->opinie->removeElement($opinia)) {
            // Set czlonek to null if the relationship allows it
            if ($opinia->getCzlonek() === $this) {
                // Don't set to null if it's required
            }
        }

        return $this;
    }

    /**
     * Relacje kolekcji: Platnosc (mappedBy="darczyca").
     */
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
                // Don't set to null if it's required, let Doctrine handle it
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, SkladkaCzlonkowska>
     */
    public function getSkladkiCzlonkowskie(): Collection
    {
        return $this->skladkiCzlonkowskie;
    }

    public function addSkladkaCzlonkowska(SkladkaCzlonkowska $skladka): self
    {
        if (!$this->skladkiCzlonkowskie->contains($skladka)) {
            $this->skladkiCzlonkowskie->add($skladka);
            $skladka->setCzlonek($this);
        }

        return $this;
    }

    public function removeSkladkaCzlonkowska(SkladkaCzlonkowska $skladka): self
    {
        if ($this->skladkiCzlonkowskie->removeElement($skladka)) {
            if ($skladka->getCzlonek() === $this) {
                // Don't set to null if it's required, let Doctrine handle it
            }
        }

        return $this;
    }

    public function getFullName(): string
    {
        return trim(($this->imie ?? '').' '.($this->nazwisko ?? ''));
    }

    // Gettery i settery dla postępu kandydata
    public function getDataWypelnienieFormularza(): ?\DateTimeInterface
    {
        return $this->dataWypelnienieFormularza;
    }

    public function setDataWypelnienieFormularza(?\DateTimeInterface $dataWypelnienieFormularza): self
    {
        $this->dataWypelnienieFormularza = $dataWypelnienieFormularza;

        return $this;
    }

    public function getDataWeryfikacjaDokumentow(): ?\DateTimeInterface
    {
        return $this->dataWeryfikacjaDokumentow;
    }

    public function setDataWeryfikacjaDokumentow(?\DateTimeInterface $dataWeryfikacjaDokumentow): self
    {
        $this->dataWeryfikacjaDokumentow = $dataWeryfikacjaDokumentow;

        return $this;
    }

    public function getDataRozmowaPrekwalifikacyjna(): ?\DateTimeInterface
    {
        return $this->dataRozmowaPrekwalifikacyjna;
    }

    public function setDataRozmowaPrekwalifikacyjna(?\DateTimeInterface $dataRozmowaPrekwalifikacyjna): self
    {
        $this->dataRozmowaPrekwalifikacyjna = $dataRozmowaPrekwalifikacyjna;

        return $this;
    }

    public function getDataOpiniaRadyOddzialu(): ?\DateTimeInterface
    {
        return $this->dataOpiniaRadyOddzialu;
    }

    public function setDataOpiniaRadyOddzialu(?\DateTimeInterface $dataOpiniaRadyOddzialu): self
    {
        $this->dataOpiniaRadyOddzialu = $dataOpiniaRadyOddzialu;

        return $this;
    }

    public function getDataDecyzjaZarzadu(): ?\DateTimeInterface
    {
        return $this->dataDecyzjaZarzadu;
    }

    public function setDataDecyzjaZarzadu(?\DateTimeInterface $dataDecyzjaZarzadu): self
    {
        $this->dataDecyzjaZarzadu = $dataDecyzjaZarzadu;

        return $this;
    }

    public function getDataPrzyjecieUroczyste(): ?\DateTimeInterface
    {
        return $this->dataPrzyjecieUroczyste;
    }

    public function setDataPrzyjecieUroczyste(?\DateTimeInterface $dataPrzyjecieUroczyste): self
    {
        $this->dataPrzyjecieUroczyste = $dataPrzyjecieUroczyste;

        return $this;
    }

    /**
     * Oblicza procent ukończenia procesu rekrutacyjnego kandydata (8 etapów).
     */
    public function getPostepKandydata(): int
    {
        if ('kandydat' !== $this->typUzytkownika) {
            return 0;
        }

        if (!$this->postepKandydataEntity) {
            return 0;
        }

        return $this->postepKandydataEntity->getPostepProcentowy();
    }

    /**
     * Zwraca szczegółowe informacje o postępie kandydata (nowy system 8 kroków).
     *
     * @return array<string, mixed>
     */
    public function getSzczegoryPostepuKandydata(): array
    {
        if (!$this->postepKandydataEntity) {
            // Jeśli nie ma encji postępu, zwracamy pusty stan dla wszystkich kroków
            return [
                1 => [
                    'nazwa' => 'Opłacenie składki',
                    'data' => null,
                    'wykonane' => false,
                    'odznaczyl' => null,
                ],
                2 => [
                    'nazwa' => 'Wgranie zdjęcia',
                    'data' => null,
                    'wykonane' => false,
                    'odznaczyl' => null,
                ],
                3 => [
                    'nazwa' => 'Wgranie CV',
                    'data' => null,
                    'wykonane' => false,
                    'odznaczyl' => null,
                ],
                4 => [
                    'nazwa' => 'Uzupełnienie profilu',
                    'data' => null,
                    'wykonane' => false,
                    'odznaczyl' => null,
                ],
                5 => [
                    'nazwa' => 'Rozmowa prekwalifikacyjna',
                    'data' => null,
                    'wykonane' => false,
                    'odznaczyl' => null,
                ],
                6 => [
                    'nazwa' => 'Opinia Rady oddziału',
                    'data' => null,
                    'wykonane' => false,
                    'odznaczyl' => null,
                ],
                7 => [
                    'nazwa' => 'Udział w zebraniach przez 3 miesiące',
                    'data' => null,
                    'wykonane' => false,
                    'odznaczyl' => null,
                ],
                8 => [
                    'nazwa' => 'Decyzja',
                    'data' => null,
                    'wykonane' => false,
                    'odznaczyl' => null,
                ],
            ];
        }

        return $this->postepKandydataEntity->getKrokiPostepuArray();
    }

    /**
     * Zwraca kolor paska postępu na podstawie procentu ukończenia.
     */
    public function getKolorPaskaPostepu(): string
    {
        $procent = $this->getPostepKandydata();

        if (0 === $procent) {
            return 'bg-secondary';
        }
        if ($procent < 34) {
            return 'bg-danger';
        }
        if ($procent < 67) {
            return 'bg-warning';
        }
        if ($procent < 100) {
            return 'bg-info';
        }

        return 'bg-success';
    }

    public function isSkladkaOplacona(): ?bool
    {
        return $this->skladkaOplacona;
    }

    public function setSkladkaOplacona(?bool $skladkaOplacona): self
    {
        $this->skladkaOplacona = $skladkaOplacona;

        return $this;
    }

    public function getDataOplaceniaSkladki(): ?\DateTimeInterface
    {
        return $this->dataOplaceniaSkladki;
    }

    public function setDataOplaceniaSkladki(?\DateTimeInterface $dataOplaceniaSkladki): self
    {
        $this->dataOplaceniaSkladki = $dataOplaceniaSkladki;

        return $this;
    }

    /**
     * Zwraca status składki z kolorowym wskaźnikiem - uproszczona wersja dla listy.
     */
    /**
     * @return array<string, mixed>
     */
    public function getStatusSkladki(): array
    {
        if (true === $this->skladkaOplacona) {
            return [
                'status' => 'Opłacona',
                'class' => 'success',
                'icon' => 'fas fa-check',
            ];
        } else {
            return [
                'status' => 'Nieopłacona',
                'class' => 'danger',
                'icon' => 'fas fa-times',
            ];
        }
    }

    public function getKwotaSkladki(): ?string
    {
        return $this->kwotaSkladki;
    }

    public function setKwotaSkladki(?string $kwotaSkladki): self
    {
        $this->kwotaSkladki = $kwotaSkladki;

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

    /**
     * Zwraca szczegółowe informacje o składce dla widoku szczegółów.
     */
    /**
     * @return array<string, mixed>
     */
    public function getSzczegolySkladki(): array
    {
        $kwota = $this->kwotaSkladki ?: '20.00';
        $currency = 'PLN';

        if (true === $this->skladkaOplacona) {
            $status = 'Opłacona';
            $class = 'success';
            $icon = 'fas fa-check-circle';
            $dataWaznosci = $this->dataWaznosciSkladki;
            $czyPrzeterminowana = $dataWaznosci && $dataWaznosci < new \DateTime();

            if ($czyPrzeterminowana) {
                $status = 'Przeterminowana';
                $class = 'warning';
                $icon = 'fas fa-exclamation-triangle';
            }
        } else {
            $status = 'Nieopłacona';
            $class = 'danger';
            $icon = 'fas fa-times-circle';
            $dataWaznosci = null;
            $czyPrzeterminowana = false;
        }

        return [
            'status' => $status,
            'class' => $class,
            'icon' => $icon,
            'kwota' => $kwota,
            'currency' => $currency,
            'dataOplacenia' => $this->dataOplaceniaSkladki,
            'dataWaznosci' => $dataWaznosci,
            'czyPrzeterminowana' => $czyPrzeterminowana,
        ];
    }

    // Gettery i settery dla zatrudnienia w spółkach
    public function getZatrudnienieSpolkiMiejskie(): ?string
    {
        return $this->zatrudnienieSpolkiMiejskie;
    }

    public function setZatrudnienieSpolkiMiejskie(?string $zatrudnienieSpolkiMiejskie): self
    {
        $this->zatrudnienieSpolkiMiejskie = $zatrudnienieSpolkiMiejskie;

        return $this;
    }

    public function getZatrudnienieSpolkiSkarbuPanstwa(): ?string
    {
        return $this->zatrudnienieSpolkiSkarbuPanstwa;
    }

    public function setZatrudnienieSpolkiSkarbuPanstwa(?string $zatrudnienieSpolkiSkarbuPanstwa): self
    {
        $this->zatrudnienieSpolkiSkarbuPanstwa = $zatrudnienieSpolkiSkarbuPanstwa;

        return $this;
    }

    public function getZatrudnienieSpolkiKomunalne(): ?string
    {
        return $this->zatrudnienieSpolkiKomunalne;
    }

    public function setZatrudnienieSpolkiKomunalne(?string $zatrudnienieSpolkiKomunalne): self
    {
        $this->zatrudnienieSpolkiKomunalne = $zatrudnienieSpolkiKomunalne;

        return $this;
    }

    // Historia wyborów
    public function getHistoriaWyborow(): ?string
    {
        return $this->historiaWyborow;
    }

    public function setHistoriaWyborow(?string $historiaWyborow): self
    {
        $this->historiaWyborow = $historiaWyborow;

        return $this;
    }

    // Media społecznościowe
    /**
     * @return array<string, string>|null
     */
    public function getMediaSpolecznosciowe(): ?array
    {
        return $this->mediaSpolecznosciowe;
    }

    /**
     * @param array<string, string>|null $mediaSpolecznosciowe
     */
    public function setMediaSpolecznosciowe(?array $mediaSpolecznosciowe): self
    {
        $this->mediaSpolecznosciowe = $mediaSpolecznosciowe;

        return $this;
    }

    /**
     * Zwraca konfigurowane media społecznościowe z ikonkami.
     */
    /**
     * @return array<int, array<string, string>>
     */
    public function getSocialMediaLinks(): array
    {
        $media = $this->mediaSpolecznosciowe ?: [];
        $socialMedia = [];

        $platforms = [
            'facebook' => ['icon' => 'fab fa-facebook', 'color' => '#1877f2', 'name' => 'Facebook'],
            'twitter' => ['icon' => 'fab fa-twitter', 'color' => '#1da1f2', 'name' => 'Twitter'],
            'instagram' => ['icon' => 'fab fa-instagram', 'color' => '#e4405f', 'name' => 'Instagram'],
            'tiktok' => ['icon' => 'fab fa-tiktok', 'color' => '#000000', 'name' => 'TikTok'],
            'linkedin' => ['icon' => 'fab fa-linkedin', 'color' => '#0077b5', 'name' => 'LinkedIn'],
            'youtube' => ['icon' => 'fab fa-youtube', 'color' => '#ff0000', 'name' => 'YouTube'],
        ];

        foreach ($media as $platform => $url) {
            if (!empty($url) && isset($platforms[$platform])) {
                $socialMedia[] = [
                    'platform' => $platform,
                    'url' => $url,
                    'icon' => $platforms[$platform]['icon'],
                    'color' => $platforms[$platform]['color'],
                    'name' => $platforms[$platform]['name'],
                ];
            }
        }

        return $socialMedia;
    }

    // Byli członkowie
    public function getOldId(): ?string
    {
        return $this->oldId;
    }

    public function setOldId(?string $oldId): self
    {
        $this->oldId = $oldId;

        return $this;
    }

    public function isCzyBylyCzlonek(): bool
    {
        return $this->czyBylyCzlonek;
    }

    public function setCzyBylyCzlonek(bool $czyBylyCzlonek): self
    {
        $this->czyBylyCzlonek = $czyBylyCzlonek;

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

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $previousLoginAt = null;

    /**
     * Sprawdza czy użytkownik ma aktywny dostęp do systemu.
     */
    public function hasActiveAccess(): bool
    {
        return !$this->czyBylyCzlonek;
    }

    /**
     * Sprawdza czy użytkownik ma daną rolę.
     */
    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles());
    }

    public function getLastLoginAt(): ?\DateTimeInterface
    {
        return $this->lastLoginAt;
    }

    public function setLastLoginAt(?\DateTimeInterface $lastLoginAt): static
    {
        $this->lastLoginAt = $lastLoginAt;

        return $this;
    }

    public function getPreviousLoginAt(): ?\DateTimeInterface
    {
        return $this->previousLoginAt;
    }

    public function setPreviousLoginAt(?\DateTimeInterface $previousLoginAt): static
    {
        $this->previousLoginAt = $previousLoginAt;

        return $this;
    }

    public function getFormattedLastLogin(): string
    {
        if (!$this->lastLoginAt) {
            return 'Nigdy';
        }

        $now = new \DateTime();
        $diff = $now->diff($this->lastLoginAt);

        if ($diff->days > 0) {
            if (1 == $diff->days) {
                return 'Wczoraj, '.$this->lastLoginAt->format('H:i');
            } elseif ($diff->days < 7) {
                return $diff->days.' dni temu, '.$this->lastLoginAt->format('H:i');
            } else {
                return $this->lastLoginAt->format('d.m.Y, H:i');
            }
        } elseif ($diff->h > 0) {
            return 'Dzisiaj, '.$this->lastLoginAt->format('H:i');
        } elseif ($diff->i > 0) {
            return $diff->i.' min. temu';
        } else {
            return 'Przed chwilą';
        }
    }

    /**
     * Zwraca najwyższą funkcję pełnioną przez użytkownika.
     */
    public function getNajwyzszaFunkcja(): string
    {
        $roles = $this->getRoles();

        // Hierarchia funkcji - od najwyższej do najniższej
        $hierarchia = [
            'ROLE_ADMIN' => 'Administrator',
            'ROLE_PREZES_PARTII' => 'Prezes Partii',
            'ROLE_WICEPREZES_PARTII' => 'Wiceprezes Partii',
            'ROLE_SEKRETARZ_PARTII' => 'Sekretarz Partii',
            'ROLE_SKARBNIK_PARTII' => 'Skarbnik Partii',
            'ROLE_PELNOMOCNIK_STRUKTUR' => 'Pełnomocnik ds. Struktur',
            'ROLE_PREZES_REGIONU' => 'Prezes Regionu',
            'ROLE_PREZES_OKREGU' => 'Prezes Okręgu',
            'ROLE_WICEPREZES_OKREGU' => 'Wiceprezes Okręgu',
            'ROLE_SEKRETARZ_OKREGU' => 'Sekretarz Okręgu',
            'ROLE_SKARBNIK_OKREGU' => 'Skarbnik Okręgu',
            'ROLE_INFORMATYK_OKREGOWY' => 'Informatyk Okręgowy',
            'ROLE_OKREGOWY_PELNOMOCNIK' => 'Okręgowy Pełnomocnik',
            'ROLE_PRZEWODNICZACY_ODDZIALU' => 'Przewodniczący Oddziału',
            'ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU' => 'Zastępca Przewodniczącego Oddziału',
            'ROLE_SEKRETARZ_ODDZIALU' => 'Sekretarz Oddziału',
            'ROLE_OBSERWATOR_ZEBRANIA' => 'Obserwator Zebrania',
            'ROLE_PROWADZACY_ZEBRANIA' => 'Prowadzący Zebrania',
            'ROLE_PROTOKOLANT_ZEBRANIA' => 'Protokolant Zebrania',
            'ROLE_FUNKCYJNY' => 'Funkcyjny',
            'ROLE_CZLONEK_PARTII' => 'Członek Partii',
            'ROLE_KANDYDAT_PARTII' => 'Kandydat na Członka',
            'ROLE_CZLONEK_MLODZIEZOWKI' => 'Członek Młodzieżówki',
            'ROLE_SYMPATYK' => 'Sympatyk',
            'ROLE_DARCZYCA' => 'Darczyńca',
            'ROLE_USER' => 'Użytkownik',
        ];

        // Znajdź najwyższą funkcję w hierarchii
        foreach ($hierarchia as $role => $funkcja) {
            if (in_array($role, $roles)) {
                return $funkcja;
            }
        }

        return 'Użytkownik';
    }

    /**
     * Oblicza wiek na podstawie daty urodzenia.
     */
    public function getWiek(): ?int
    {
        if (!$this->dataUrodzenia) {
            return null;
        }

        $today = new \DateTime();
        $age = $today->diff($this->dataUrodzenia)->y;
        
        return $age;
    }

    public function getNumerKontaBankowego(): ?string
    {
        return $this->numerKontaBankowego;
    }

    public function setNumerKontaBankowego(?string $numerKontaBankowego): self
    {
        $this->numerKontaBankowego = $numerKontaBankowego;
        return $this;
    }

    public function getPostepKandydataEntity(): ?PostepKandydata
    {
        return $this->postepKandydataEntity;
    }

    public function setPostepKandydataEntity(?PostepKandydata $postepKandydataEntity): self
    {
        $this->postepKandydataEntity = $postepKandydataEntity;
        return $this;
    }

    /**
     * Zwraca pełny adres użytkownika.
     */
    public function getFullAddress(): ?string
    {
        return $this->adresZamieszkania ?? 'Brak adresu';
    }

    /**
     * Zwraca ładnie sformatowane nazwy funkcji na podstawie ról użytkownika.
     */
    public function getFunkcjeFromRoles(): array
    {
        $roleToFunctionMap = [
            // Zarząd krajowy
            'ROLE_PREZES_PARTII' => 'Prezes Partii',
            'ROLE_WICEPREZES_PARTII' => 'Wiceprezes Partii', 
            'ROLE_SEKRETARZ_PARTII' => 'Sekretarz Partii',
            'ROLE_SKARBNIK_PARTII' => 'Skarbnik Partii',
            'ROLE_RZECZNIK_PRASOWY' => 'Rzecznik Prasowy',
            
            // Zarząd regionu
            'ROLE_PREZES_REGIONU' => 'Prezes Regionu',
            
            // Zarząd okręgu
            'ROLE_PREZES_OKREGU' => 'Prezes Okręgu',
            'ROLE_WICEPREZES_OKREGU' => 'Wiceprezes Okręgu',
            'ROLE_SEKRETARZ_OKREGU' => 'Sekretarz Okręgu',
            'ROLE_SKARBNIK_OKREGU' => 'Skarbnik Okręgu',
            'ROLE_PELNOMOCNIK_PRZYJMOWANIA' => 'Pełnomocnik ds. Przyjmowania',
            
            // Zarząd oddziału
            'ROLE_PRZEWODNICZACY_ODDZIALU' => 'Przewodniczący Oddziału',
            'ROLE_ZASTEPCA_PRZEWODNICZACEGO_ODDZIALU' => 'Zastępca Przewodniczącego Oddziału',
            'ROLE_SEKRETARZ_ODDZIALU' => 'Sekretarz Oddziału',
            
            // Role tymczasowe zebrań
            'ROLE_OBSERWATOR_ZEBRANIA' => 'Obserwator Zebrania',
            'ROLE_PROTOKOLANT_ZEBRANIA' => 'Protokolant Zebrania',
            'ROLE_PROWADZACY_ZEBRANIA' => 'Prowadzący Zebrania',
        ];
        
        $funkcje = [];
        foreach ($this->roles as $role) {
            if (isset($roleToFunctionMap[$role])) {
                $funkcje[] = $roleToFunctionMap[$role];
            }
        }
        
        return array_unique($funkcje);
    }


    // Gettery i settery dla nowych pól
    public function getFunkcjePubliczne(): ?string
    {
        return $this->funkcjePubliczne;
    }

    public function setFunkcjePubliczne(?string $funkcjePubliczne): self
    {
        $this->funkcjePubliczne = $funkcjePubliczne;
        return $this;
    }

    public function getPrzynaleznosc(): ?string
    {
        return $this->przynaleznosc;
    }

    public function setPrzynaleznosc(?string $przynaleznosc): self
    {
        $this->przynaleznosc = $przynaleznosc;
        return $this;
    }

    public function getZgodaRodo(): ?bool
    {
        return $this->zgodaRodo;
    }

    public function setZgodaRodo(?bool $zgodaRodo): self
    {
        $this->zgodaRodo = $zgodaRodo;
        return $this;
    }

    public function getDataZgodyRodo(): ?\DateTimeInterface
    {
        return $this->dataZgodyRodo;
    }

    public function setDataZgodyRodo(?\DateTimeInterface $dataZgodyRodo): self
    {
        $this->dataZgodyRodo = $dataZgodyRodo;
        return $this;
    }
}
