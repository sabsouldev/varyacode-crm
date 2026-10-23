<?php

namespace App\Entity;

use App\Repository\ModeleMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Modèle de mail ou trame d'appel. Le corps accepte les variables
 * {prenom}, {entreprise} et {constat}.
 */
#[ORM\Entity(repositoryClass: ModeleMessageRepository::class)]
class ModeleMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private string $nom;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $objet;

    #[ORM\Column(type: Types::TEXT)]
    private string $corps;

    public function __construct(string $nom, ?string $objet, string $corps)
    {
        $this->nom = $nom;
        $this->objet = $objet;
        $this->corps = $corps;
    }

    /** @param array<string, string|null> $variables */
    public function rendre(array $variables): string
    {
        $remplacements = [];
        foreach ($variables as $cle => $valeur) {
            $remplacements['{'.$cle.'}'] = $valeur ?? '';
        }

        return strtr($this->corps, $remplacements);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getObjet(): ?string
    {
        return $this->objet;
    }

    public function setObjet(?string $objet): static
    {
        $this->objet = $objet;

        return $this;
    }

    public function getCorps(): string
    {
        return $this->corps;
    }

    public function setCorps(string $corps): static
    {
        $this->corps = $corps;

        return $this;
    }
}
