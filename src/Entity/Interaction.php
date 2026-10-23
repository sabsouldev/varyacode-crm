<?php

namespace App\Entity;

use App\Enum\Canal;
use App\Enum\TypeInteraction;
use App\Repository\InteractionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InteractionRepository::class)]
#[ORM\Index(columns: ['date'])]
class Interaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'interactions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Prospect $prospect;

    #[ORM\Column]
    private \DateTimeImmutable $date;

    #[ORM\Column(enumType: TypeInteraction::class)]
    private TypeInteraction $type;

    #[ORM\Column(enumType: Canal::class, nullable: true)]
    private ?Canal $canal;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $resume;

    public function __construct(Prospect $prospect, \DateTimeImmutable $date, TypeInteraction $type, ?Canal $canal = null, ?string $resume = null)
    {
        $this->prospect = $prospect;
        $this->date = $date;
        $this->type = $type;
        $this->canal = $canal;
        $this->resume = $resume;
        $prospect->ajouterInteraction($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProspect(): Prospect
    {
        return $this->prospect;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function getType(): TypeInteraction
    {
        return $this->type;
    }

    public function getCanal(): ?Canal
    {
        return $this->canal;
    }

    public function getResume(): ?string
    {
        return $this->resume;
    }
}
