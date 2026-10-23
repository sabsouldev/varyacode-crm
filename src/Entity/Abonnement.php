<?php

namespace App\Entity;

use App\Enum\Offre;
use App\Repository\AbonnementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AbonnementRepository::class)]
class Abonnement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Prospect $prospect;

    #[ORM\Column(enumType: Offre::class)]
    private Offre $offre;

    /** En centimes, par mois. */
    #[ORM\Column]
    private int $montantMensuel;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $debut;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $finEngagement;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $stripeCustomerId = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $resilieLe = null;

    public function __construct(Prospect $prospect, Offre $offre, int $montantMensuel, \DateTimeImmutable $debut, int $moisEngagement = 12)
    {
        $this->prospect = $prospect;
        $this->offre = $offre;
        $this->montantMensuel = $montantMensuel;
        $this->debut = $debut;
        $this->finEngagement = $debut->modify(\sprintf('+%d months', $moisEngagement));
    }

    public function estActif(\DateTimeImmutable $le): bool
    {
        return $this->debut <= $le && (null === $this->resilieLe || $this->resilieLe > $le);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProspect(): Prospect
    {
        return $this->prospect;
    }

    public function getOffre(): Offre
    {
        return $this->offre;
    }

    public function getMontantMensuel(): int
    {
        return $this->montantMensuel;
    }

    public function getDebut(): \DateTimeImmutable
    {
        return $this->debut;
    }

    public function getFinEngagement(): \DateTimeImmutable
    {
        return $this->finEngagement;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripeCustomerId;
    }

    public function setStripeCustomerId(?string $stripeCustomerId): static
    {
        $this->stripeCustomerId = $stripeCustomerId;

        return $this;
    }

    public function getResilieLe(): ?\DateTimeImmutable
    {
        return $this->resilieLe;
    }

    public function setResilieLe(?\DateTimeImmutable $resilieLe): static
    {
        $this->resilieLe = $resilieLe;

        return $this;
    }
}
