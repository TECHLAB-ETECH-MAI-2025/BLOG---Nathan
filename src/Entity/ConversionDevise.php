<?php

namespace App\Entity;

use App\Repository\ConversionDeviseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ConversionDeviseRepository::class)]
class ConversionDevise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $montant = null;

    #[ORM\Column(length: 30)]
    private ?string $deviseSource = null;

    #[ORM\Column(length: 30)]
    private ?string $deviseCible = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 6)]
    private ?string $tauxChange = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2)]
    private ?string $montantConverti = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $fraisBancaires = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, nullable: true)]
    private ?string $montantFinal = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateTransaction = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $utilisateur = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->dateTransaction = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(string $montant): static
    {
        $this->montant = $montant;
        return $this;
    }

    public function getDeviseSource(): ?string
    {
        return $this->deviseSource;
    }

    public function setDeviseSource(string $deviseSource): static
    {
        $this->deviseSource = $deviseSource;
        return $this;
    }

    public function getDeviseCible(): ?string
    {
        return $this->deviseCible;
    }

    public function setDeviseCible(string $deviseCible): static
    {
        $this->deviseCible = $deviseCible;
        return $this;
    }

    public function getTauxChange(): ?string
    {
        return $this->tauxChange;
    }

    public function setTauxChange(string $tauxChange): static
    {
        $this->tauxChange = $tauxChange;
        return $this;
    }

    public function getMontantConverti(): ?string
    {
        return $this->montantConverti;
    }

    public function setMontantConverti(string $montantConverti): static
    {
        $this->montantConverti = $montantConverti;
        return $this;
    }

    public function getFraisBancaires(): ?string
    {
        return $this->fraisBancaires;
    }

    public function setFraisBancaires(?string $fraisBancaires): static
    {
        $this->fraisBancaires = $fraisBancaires;
        return $this;
    }

    public function getMontantFinal(): ?string
    {
        return $this->montantFinal;
    }

    public function setMontantFinal(?string $montantFinal): static
    {
        $this->montantFinal = $montantFinal;
        return $this;
    }

    public function getDateTransaction(): ?\DateTimeInterface
    {
        return $this->dateTransaction;
    }

    public function setDateTransaction(\DateTimeInterface $dateTransaction): static
    {
        $this->dateTransaction = $dateTransaction;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }
}
