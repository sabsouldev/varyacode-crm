<?php

namespace App\Entity;

use App\Enum\TypeTache;
use App\Repository\TacheRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TacheRepository::class)]
#[ORM\Index(columns: ['echeance'])]
class Tache
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'taches')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Prospect $prospect;

    #[ORM\Column(enumType: TypeTache::class)]
    private TypeTache $type;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $echeance;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description;

    /** Créée par une règle de relance plutôt qu'à la main. */
    #[ORM\Column]
    private bool $generee;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $faiteLe = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $annuleeLe = null;

    public function __construct(Prospect $prospect, TypeTache $type, \DateTimeImmutable $echeance, ?string $description = null, bool $generee = false)
    {
        $this->prospect = $prospect;
        $this->type = $type;
        $this->echeance = $echeance->setTime(0, 0);
        $this->description = $description;
        $this->generee = $generee;
        $prospect->ajouterTache($this);
    }

    public function estOuverte(): bool
    {
        return null === $this->faiteLe && null === $this->annuleeLe;
    }

    public function marquerFaite(\DateTimeImmutable $le): void
    {
        $this->faiteLe = $le;
    }

    public function annuler(\DateTimeImmutable $le): void
    {
        $this->annuleeLe = $le;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProspect(): Prospect
    {
        return $this->prospect;
    }

    public function getType(): TypeTache
    {
        return $this->type;
    }

    public function getEcheance(): \DateTimeImmutable
    {
        return $this->echeance;
    }

    public function setEcheance(\DateTimeImmutable $echeance): static
    {
        $this->echeance = $echeance->setTime(0, 0);

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isGeneree(): bool
    {
        return $this->generee;
    }

    public function getFaiteLe(): ?\DateTimeImmutable
    {
        return $this->faiteLe;
    }

    public function getAnnuleeLe(): ?\DateTimeImmutable
    {
        return $this->annuleeLe;
    }
}
