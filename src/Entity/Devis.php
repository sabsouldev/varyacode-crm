<?php

namespace App\Entity;

use App\Enum\Offre;
use App\Repository\DevisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DevisRepository::class)]
class Devis
{
    public const STATUT_ENVOYE = 'envoye';
    public const STATUT_ACCEPTE = 'accepte';
    public const STATUT_REFUSE = 'refuse';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'devis')]
    #[ORM\JoinColumn(nullable: false)]
    private Prospect $prospect;

    /** Numéro du devis dans Evolbee, par exemple D26/2784/00001. */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(enumType: Offre::class)]
    private Offre $offre;

    /** En centimes. */
    #[ORM\Column]
    private int $mensualite;

    /** En centimes. */
    #[ORM\Column]
    private int $fraisMiseEnLigne;

    #[ORM\Column]
    private bool $sansEngagement = false;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $envoyeLe;

    #[ORM\Column(length: 16)]
    private string $statut = self::STATUT_ENVOYE;

    public function __construct(Prospect $prospect, Offre $offre, \DateTimeImmutable $envoyeLe)
    {
        $this->prospect = $prospect;
        $this->offre = $offre;
        $this->envoyeLe = $envoyeLe;
        $this->mensualite = $offre->mensualite() ?? 0;
        $this->fraisMiseEnLigne = $offre->fraisMiseEnLigne() ?? 0;
        $prospect->getDevis()->add($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProspect(): Prospect
    {
        return $this->prospect;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getOffre(): Offre
    {
        return $this->offre;
    }

    public function getMensualite(): int
    {
        return $this->mensualite;
    }

    public function setMensualite(int $mensualite): static
    {
        $this->mensualite = $mensualite;

        return $this;
    }

    public function getFraisMiseEnLigne(): int
    {
        return $this->fraisMiseEnLigne;
    }

    public function setFraisMiseEnLigne(int $fraisMiseEnLigne): static
    {
        $this->fraisMiseEnLigne = $fraisMiseEnLigne;

        return $this;
    }

    public function isSansEngagement(): bool
    {
        return $this->sansEngagement;
    }

    public function setSansEngagement(bool $sansEngagement): static
    {
        $this->sansEngagement = $sansEngagement;

        return $this;
    }

    public function getEnvoyeLe(): \DateTimeImmutable
    {
        return $this->envoyeLe;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
