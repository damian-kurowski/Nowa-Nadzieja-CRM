<?php

namespace App\Entity;

use App\Repository\PrzekazMedialnyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrzekazMedialnyRepository::class)]
#[ORM\Table(name: 'przekaz_medialny')]
#[ORM\Index(columns: ['data_wyslania'], name: 'idx_przekaz_data_wyslania')]
#[ORM\Index(columns: ['status'], name: 'idx_przekaz_status')]
class PrzekazMedialny
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $tytul = null;

    #[ORM\Column(type: 'text')]
    private ?string $tresc = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $autor = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dataWyslania = null;

    #[ORM\Column]
    private int $liczbaOdbiorcow = 0;

    #[ORM\Column]
    private int $liczbaPrzeczytanych = 0;

    #[ORM\Column]
    private int $liczbaOdpowiedzi = 0;

    #[ORM\Column(length: 20)]
    private string $status = 'draft'; // draft, sending, sent, failed

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $dataUtworzenia = null;

    #[ORM\OneToMany(mappedBy: 'przekaz', targetEntity: PrzekazOdbiorca::class, cascade: ['persist', 'remove'])]
    private Collection $odbiorcy;

    #[ORM\OneToMany(mappedBy: 'przekaz', targetEntity: PrzekazOdpowiedz::class, cascade: ['persist', 'remove'])]
    private Collection $odpowiedzi;

    public function __construct()
    {
        $this->dataUtworzenia = new \DateTime();
        $this->odbiorcy = new ArrayCollection();
        $this->odpowiedzi = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTytul(): ?string
    {
        return $this->tytul;
    }

    public function setTytul(string $tytul): static
    {
        $this->tytul = $tytul;
        return $this;
    }

    public function getTresc(): ?string
    {
        return $this->tresc;
    }

    public function setTresc(string $tresc): static
    {
        $this->tresc = $tresc;
        return $this;
    }

    public function getAutor(): ?User
    {
        return $this->autor;
    }

    public function setAutor(?User $autor): static
    {
        $this->autor = $autor;
        return $this;
    }

    public function getDataWyslania(): ?\DateTimeInterface
    {
        return $this->dataWyslania;
    }

    public function setDataWyslania(?\DateTimeInterface $dataWyslania): static
    {
        $this->dataWyslania = $dataWyslania;
        return $this;
    }

    public function getLiczbaOdbiorcow(): int
    {
        return $this->liczbaOdbiorcow;
    }

    public function setLiczbaOdbiorcow(int $liczbaOdbiorcow): static
    {
        $this->liczbaOdbiorcow = $liczbaOdbiorcow;
        return $this;
    }

    public function getLiczbaPrzeczytanych(): int
    {
        return $this->liczbaPrzeczytanych;
    }

    public function setLiczbaPrzeczytanych(int $liczbaPrzeczytanych): static
    {
        $this->liczbaPrzeczytanych = $liczbaPrzeczytanych;
        return $this;
    }

    public function getLiczbaOdpowiedzi(): int
    {
        return $this->liczbaOdpowiedzi;
    }

    public function setLiczbaOdpowiedzi(int $liczbaOdpowiedzi): static
    {
        $this->liczbaOdpowiedzi = $liczbaOdpowiedzi;
        return $this;
    }

    public function getStatus(): string
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

    /**
     * @return Collection<int, PrzekazOdbiorca>
     */
    public function getOdbiorcy(): Collection
    {
        return $this->odbiorcy;
    }

    public function addOdbiorca(PrzekazOdbiorca $odbiorca): static
    {
        if (!$this->odbiorcy->contains($odbiorca)) {
            $this->odbiorcy->add($odbiorca);
            $odbiorca->setPrzekazMedialny($this);
        }

        return $this;
    }

    public function removeOdbiorca(PrzekazOdbiorca $odbiorca): static
    {
        if ($this->odbiorcy->removeElement($odbiorca)) {
            if ($odbiorca->getPrzekazMedialny() === $this) {
                $odbiorca->setPrzekazMedialny(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PrzekazOdpowiedz>
     */
    public function getOdpowiedzi(): Collection
    {
        return $this->odpowiedzi;
    }

    public function addOdpowiedz(PrzekazOdpowiedz $odpowiedz): static
    {
        if (!$this->odpowiedzi->contains($odpowiedz)) {
            $this->odpowiedzi->add($odpowiedz);
            $odpowiedz->setPrzekazMedialny($this);
        }

        return $this;
    }

    public function removeOdpowiedz(PrzekazOdpowiedz $odpowiedz): static
    {
        if ($this->odpowiedzi->removeElement($odpowiedz)) {
            if ($odpowiedz->getPrzekazMedialny() === $this) {
                $odpowiedz->setPrzekazMedialny(null);
            }
        }

        return $this;
    }

    public function getProcentPrzeczytanych(): float
    {
        if ($this->liczbaOdbiorcow === 0) {
            return 0;
        }
        return round(($this->liczbaPrzeczytanych / $this->liczbaOdbiorcow) * 100, 2);
    }

    public function getProcentOdpowiedzi(): float
    {
        if ($this->liczbaOdbiorcow === 0) {
            return 0;
        }
        return round(($this->liczbaOdpowiedzi / $this->liczbaOdbiorcow) * 100, 2);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isSending(): bool
    {
        return $this->status === 'sending';
    }
}
