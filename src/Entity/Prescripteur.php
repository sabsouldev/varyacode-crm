<?php

namespace App\Entity;

use App\Repository\PrescripteurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Apporteur d'affaires, prescripteur ou parrain (Léa, BGE, Club IVS, client parrain).
 */
#[ORM\Entity(repositoryClass: PrescripteurRepository::class)]
class Prescripteur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $nom;

    /** apporteur, prescripteur ou parrain. */
    #[ORM\Column(length: 32)]
    private string $type;

    /** Part de la 1re année d'abonnement reversée, en pourcentage (10 pour Léa). */
    #[ORM\Column(nullable: true)]
    private ?int $commissionPourcent = null;

    /** @var Collection<int, Prospect> */
    #[ORM\OneToMany(targetEntity: Prospect::class, mappedBy: 'prescripteur')]
    private Collection $prospects;

    public function __construct(string $nom, string $type)
    {
        $this->nom = $nom;
        $this->type = $type;
        $this->prospects = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCommissionPourcent(): ?int
    {
        return $this->commissionPourcent;
    }

    public function setCommissionPourcent(?int $commissionPourcent): static
    {
        $this->commissionPourcent = $commissionPourcent;

        return $this;
    }

    /** @return Collection<int, Prospect> */
    public function getProspects(): Collection
    {
        return $this->prospects;
    }
}
