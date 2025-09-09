<?php

namespace App\Entity;

use App\Repository\RegionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegionRepository::class)]
class Region
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nazwa;

    #[ORM\Column(length: 50)]
    private string $wojewodztwo;

    /**
     * @var Collection<int, Okreg>
     */
    #[ORM\OneToMany(targetEntity: Okreg::class, mappedBy: 'region')]
    private Collection $okregi;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(mappedBy: 'region', targetEntity: User::class)]
    private Collection $prezesi;

    public function __construct()
    {
        $this->okregi = new ArrayCollection();
        $this->prezesi = new ArrayCollection();
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

    public function getWojewodztwo(): string
    {
        return $this->wojewodztwo;
    }

    public function setWojewodztwo(string $wojewodztwo): self
    {
        $this->wojewodztwo = $wojewodztwo;

        return $this;
    }

    /**
     * @return Collection<int, Okreg>
     */
    public function getOkregi(): Collection
    {
        return $this->okregi;
    }

    public function addOkreg(Okreg $okreg): self
    {
        if (!$this->okregi->contains($okreg)) {
            $this->okregi->add($okreg);
            $okreg->setRegion($this);
        }

        return $this;
    }

    public function removeOkreg(Okreg $okreg): self
    {
        if ($this->okregi->removeElement($okreg)) {
            if ($okreg->getRegion() === $this) {
                $okreg->setRegion(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getPrezesi(): Collection
    {
        return $this->prezesi;
    }

    public function addPrezes(User $user): self
    {
        if (!$this->prezesi->contains($user)) {
            $this->prezesi->add($user);
            $user->setRegion($this);
        }

        return $this;
    }

    public function removePrezes(User $user): self
    {
        if ($this->prezesi->removeElement($user)) {
            if ($user->getRegion() === $this) {
                $user->setRegion(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nazwa;
    }
}