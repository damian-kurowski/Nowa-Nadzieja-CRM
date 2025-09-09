<?php

namespace App\Entity;

use App\Repository\OpiniaRadyOddzialuRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OpiniaRadyOddzialuRepository::class)]
class OpiniaRadyOddzialu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'opinie')]
    #[ORM\JoinColumn(nullable: false)]
    private User $czlonek;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $autor;

    #[ORM\Column(type: Types::TEXT)]
    private string $trescOpinii;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataDodania;

    #[ORM\Column]
    private bool $publiczna = false;

    public function __construct()
    {
        $this->dataDodania = new \DateTime();
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

    public function getAutor(): User
    {
        return $this->autor;
    }

    public function setAutor(User $autor): self
    {
        $this->autor = $autor;

        return $this;
    }

    public function getTrescOpinii(): string
    {
        return $this->trescOpinii;
    }

    public function setTrescOpinii(string $trescOpinii): self
    {
        $this->trescOpinii = $trescOpinii;

        return $this;
    }

    public function getDataDodania(): \DateTimeInterface
    {
        return $this->dataDodania;
    }

    public function setDataDodania(\DateTimeInterface $dataDodania): self
    {
        $this->dataDodania = $dataDodania;

        return $this;
    }

    public function isPubliczna(): bool
    {
        return $this->publiczna;
    }

    public function setPubliczna(bool $publiczna): self
    {
        $this->publiczna = $publiczna;

        return $this;
    }
}
