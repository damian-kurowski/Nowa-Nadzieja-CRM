<?php

namespace App\Entity;

use App\Repository\OkregRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OkregRepository::class)]
class Okreg
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nazwa;

    #[ORM\Column]
    private int $numer; // 1-41

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siedziba = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $skrot = null;

    #[ORM\ManyToOne(targetEntity: Region::class, inversedBy: 'okregi')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Region $region = null;

    /**
     * @var Collection<int, Oddzial>
     */
    #[ORM\OneToMany(targetEntity: Oddzial::class, mappedBy: 'okreg')]
    private Collection $oddzialy;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(mappedBy: 'okreg', targetEntity: User::class)]
    private Collection $czlonkowie;

    public function __construct()
    {
        $this->oddzialy = new ArrayCollection();
        $this->czlonkowie = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNazwa(): string
    {
        return $this->nazwa;
    }

    public function setNazwa(string $nazwa): self
    {
        $this->nazwa = $nazwa;

        return $this;
    }

    public function getNumer(): int
    {
        return $this->numer;
    }

    public function setNumer(int $numer): self
    {
        $this->numer = $numer;

        return $this;
    }

    public function getSiedziba(): ?string
    {
        return $this->siedziba;
    }

    public function setSiedziba(?string $siedziba): self
    {
        $this->siedziba = $siedziba;

        return $this;
    }

    public function getSkrot(): ?string
    {
        return $this->skrot;
    }

    public function setSkrot(?string $skrot): self
    {
        $this->skrot = $skrot;

        return $this;
    }

    /**
     * @return Collection<int, Oddzial>
     */
    public function getOddzialy(): Collection
    {
        return $this->oddzialy;
    }

    public function addOddzial(Oddzial $oddzial): self
    {
        if (!$this->oddzialy->contains($oddzial)) {
            $this->oddzialy->add($oddzial);
            $oddzial->setOkreg($this);
        }

        return $this;
    }

    public function removeOddzial(Oddzial $oddzial): self
    {
        if ($this->oddzialy->removeElement($oddzial)) {
            // Don't set okreg to null since it's not nullable
            // The relationship will be handled by Doctrine
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getCzlonkowie(): Collection
    {
        return $this->czlonkowie;
    }

    public function addCzlonek(User $user): self
    {
        if (!$this->czlonkowie->contains($user)) {
            $this->czlonkowie->add($user);
            $user->setOkreg($this);
        }

        return $this;
    }

    public function removeCzlonek(User $user): self
    {
        if ($this->czlonkowie->removeElement($user)) {
            if ($user->getOkreg() === $this) {
                // Don't set okreg to null since it's not nullable
                // The relationship will be handled by Doctrine
            }
        }

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

    public function __toString(): string
    {
        return $this->nazwa;
    }
}
