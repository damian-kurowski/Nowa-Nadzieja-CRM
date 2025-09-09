<?php

namespace App\Entity;

use App\Repository\OddzialRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OddzialRepository::class)]
class Oddzial
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nazwa;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $powiat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $siedziba = null;

    #[ORM\ManyToOne(targetEntity: Okreg::class, inversedBy: 'oddzialy')]
    #[ORM\JoinColumn(nullable: false)]
    private Okreg $okreg;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(mappedBy: 'oddzial', targetEntity: User::class)]
    private Collection $czlonkowie;

    public function __construct()
    {
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

    public function getPowiat(): ?string
    {
        return $this->powiat;
    }

    public function setPowiat(?string $powiat): self
    {
        $this->powiat = $powiat;

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

    public function getOkreg(): Okreg
    {
        return $this->okreg;
    }

    public function setOkreg(Okreg $okreg): self
    {
        $this->okreg = $okreg;

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
            $user->setOddzial($this);
        }

        return $this;
    }

    public function removeCzlonek(User $user): self
    {
        if ($this->czlonkowie->removeElement($user)) {
            if ($user->getOddzial() === $this) {
                $user->setOddzial(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->nazwa;
    }
}
