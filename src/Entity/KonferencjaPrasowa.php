<?php

namespace App\Entity;

use App\Repository\KonferencjaPrasowaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KonferencjaPrasowaRepository::class)]
class KonferencjaPrasowa
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataIGodzina;

    #[ORM\Column(length: 255)]
    private string $miejsceWydarzenia;

    #[ORM\Column(length: 255)]
    private string $tytulKonferencji;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $trescKonferencji = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $linkDoNagrania = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $organizatorWydarzenia = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $numerTelefonu = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class)]
    #[ORM\JoinTable(name: 'konferencja_prasowa_mowcy')]
    private Collection $mowcy;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $zglaszajacy;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataZgloszenia;

    public function __construct()
    {
        $this->mowcy = new ArrayCollection();
        $this->dataZgloszenia = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDataIGodzina(): \DateTimeInterface
    {
        return $this->dataIGodzina;
    }

    public function setDataIGodzina(\DateTimeInterface $dataIGodzina): self
    {
        $this->dataIGodzina = $dataIGodzina;

        return $this;
    }

    public function getMiejsceWydarzenia(): string
    {
        return $this->miejsceWydarzenia;
    }

    public function setMiejsceWydarzenia(string $miejsceWydarzenia): self
    {
        $this->miejsceWydarzenia = $miejsceWydarzenia;

        return $this;
    }

    public function getTytulKonferencji(): string
    {
        return $this->tytulKonferencji;
    }

    public function setTytulKonferencji(string $tytulKonferencji): self
    {
        $this->tytulKonferencji = $tytulKonferencji;

        return $this;
    }

    public function getTrescKonferencji(): ?string
    {
        return $this->trescKonferencji;
    }

    public function setTrescKonferencji(?string $trescKonferencji): self
    {
        $this->trescKonferencji = $trescKonferencji;

        return $this;
    }

    public function getLinkDoNagrania(): ?string
    {
        return $this->linkDoNagrania;
    }

    public function setLinkDoNagrania(?string $linkDoNagrania): self
    {
        $this->linkDoNagrania = $linkDoNagrania;

        return $this;
    }

    public function getOrganizatorWydarzenia(): ?string
    {
        return $this->organizatorWydarzenia;
    }

    public function setOrganizatorWydarzenia(?string $organizatorWydarzenia): self
    {
        $this->organizatorWydarzenia = $organizatorWydarzenia;

        return $this;
    }

    public function getNumerTelefonu(): ?string
    {
        return $this->numerTelefonu;
    }

    public function setNumerTelefonu(?string $numerTelefonu): self
    {
        $this->numerTelefonu = $numerTelefonu;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMowcy(): Collection
    {
        return $this->mowcy;
    }

    public function addMowca(User $mowca): self
    {
        if (!$this->mowcy->contains($mowca)) {
            $this->mowcy->add($mowca);
        }

        return $this;
    }

    public function removeMowca(User $mowca): self
    {
        $this->mowcy->removeElement($mowca);

        return $this;
    }

    public function getZglaszajacy(): User
    {
        return $this->zglaszajacy;
    }

    public function setZglaszajacy(User $zglaszajacy): self
    {
        $this->zglaszajacy = $zglaszajacy;

        return $this;
    }

    public function getDataZgloszenia(): \DateTimeInterface
    {
        return $this->dataZgloszenia;
    }

    public function setDataZgloszenia(\DateTimeInterface $dataZgloszenia): self
    {
        $this->dataZgloszenia = $dataZgloszenia;

        return $this;
    }
}
