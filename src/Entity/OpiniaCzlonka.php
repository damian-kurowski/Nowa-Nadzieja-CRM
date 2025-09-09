<?php

namespace App\Entity;

use App\Repository\OpiniaCzlonkaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OpiniaCzlonkaRepository::class)]
class OpiniaCzlonka
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $czlonek;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $autor;

    #[ORM\Column(type: Types::TEXT)]
    private string $opinia;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dataUtworzenia;

    #[ORM\Column(length: 255)]
    private string $funkcjaAutora;

    public function __construct()
    {
        $this->dataUtworzenia = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCzlonek(): User
    {
        return $this->czlonek;
    }

    public function setCzlonek(User $czlonek): static
    {
        $this->czlonek = $czlonek;

        return $this;
    }

    public function getAutor(): User
    {
        return $this->autor;
    }

    public function setAutor(User $autor): static
    {
        $this->autor = $autor;

        return $this;
    }

    public function getOpinia(): string
    {
        return $this->opinia;
    }

    public function setOpinia(string $opinia): static
    {
        $this->opinia = $opinia;

        return $this;
    }

    public function getDataUtworzenia(): \DateTimeInterface
    {
        return $this->dataUtworzenia;
    }

    public function setDataUtworzenia(\DateTimeInterface $dataUtworzenia): static
    {
        $this->dataUtworzenia = $dataUtworzenia;

        return $this;
    }

    public function getFunkcjaAutora(): string
    {
        return $this->funkcjaAutora;
    }

    public function setFunkcjaAutora(string $funkcjaAutora): static
    {
        $this->funkcjaAutora = $funkcjaAutora;

        return $this;
    }
}
