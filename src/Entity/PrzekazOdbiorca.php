<?php

namespace App\Entity;

use App\Repository\PrzekazOdbiorcaRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PrzekazOdbiorcaRepository::class)]
#[ORM\Table(name: 'przekaz_odbiorca')]
#[ORM\Index(columns: ['status'], name: 'idx_odbiorca_status')]
#[ORM\Index(columns: ['czy_przeczytany'], name: 'idx_odbiorca_przeczytany')]
class PrzekazOdbiorca
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PrzekazMedialny::class, inversedBy: 'odbiorcy')]
    #[ORM\JoinColumn(nullable: false)]
    private ?PrzekazMedialny $przekaz = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $odbiorca = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $telegramMessageId = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dataWyslania = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dataPrzeczytania = null;

    #[ORM\Column]
    private bool $czyPrzeczytany = false;

    #[ORM\Column(length: 20)]
    private string $status = 'pending'; // pending, sent, delivered, read, failed

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $blad = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrzekazMedialny(): ?PrzekazMedialny
    {
        return $this->przekaz;
    }

    public function setPrzekazMedialny(?PrzekazMedialny $przekaz): static
    {
        $this->przekaz = $przekaz;
        return $this;
    }

    public function getOdbiorca(): ?User
    {
        return $this->odbiorca;
    }

    public function setOdbiorca(?User $odbiorca): static
    {
        $this->odbiorca = $odbiorca;
        return $this;
    }

    public function getTelegramMessageId(): ?string
    {
        return $this->telegramMessageId;
    }

    public function setTelegramMessageId(?string $telegramMessageId): static
    {
        $this->telegramMessageId = $telegramMessageId;
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

    public function getDataPrzeczytania(): ?\DateTimeInterface
    {
        return $this->dataPrzeczytania;
    }

    public function setDataPrzeczytania(?\DateTimeInterface $dataPrzeczytania): static
    {
        $this->dataPrzeczytania = $dataPrzeczytania;
        return $this;
    }

    public function isCzyPrzeczytany(): bool
    {
        return $this->czyPrzeczytany;
    }

    public function setCzyPrzeczytany(bool $czyPrzeczytany): static
    {
        $this->czyPrzeczytany = $czyPrzeczytany;
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

    public function getBlad(): ?string
    {
        return $this->blad;
    }

    public function setBlad(?string $blad): static
    {
        $this->blad = $blad;
        return $this;
    }

    public function oznaczJakoPrzeczytany(): void
    {
        $this->czyPrzeczytany = true;
        $this->dataPrzeczytania = new \DateTime();
        $this->status = 'read';
    }
}
