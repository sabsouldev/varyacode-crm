<?php

namespace App\Entity;

use App\Enum\Canal;
use App\Enum\Offre;
use App\Enum\Priorite;
use App\Enum\Secteur;
use App\Enum\SiteActuel;
use App\Enum\Source;
use App\Enum\Statut;
use App\Enum\Temperature;
use App\Repository\ProspectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProspectRepository::class)]
#[ORM\Index(columns: ['statut'])]
class Prospect
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $nom;

    #[ORM\Column(enumType: Secteur::class, nullable: true)]
    private ?Secteur $secteur = null;

    #[ORM\Column(enumType: Source::class, nullable: true)]
    private ?Source $source = null;

    #[ORM\Column(enumType: SiteActuel::class)]
    private SiteActuel $siteActuel = SiteActuel::A_VERIFIER;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Url(requireTld: true)]
    private ?string $urlSite = null;

    #[ORM\Column(enumType: Priorite::class)]
    private Priorite $priorite = Priorite::MOYENNE;

    #[ORM\Column(enumType: Offre::class)]
    private Offre $offreVisee = Offre::A_DEFINIR;

    #[ORM\Column(enumType: Canal::class, nullable: true)]
    private ?Canal $canalPrefere = null;

    /** Place courante du workflow "prospect" (valeur de App\Enum\Statut). */
    #[ORM\Column(length: 32)]
    private string $statut = Statut::A_CONTACTER->value;

    #[ORM\Column]
    private bool $besoinIdentifie = false;

    #[ORM\Column]
    private bool $capaciteBudget = false;

    #[ORM\Column]
    private bool $intentionAffirmee = false;

    #[ORM\Column]
    private int $nbRelances = 0;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $dernierContact = null;

    /** Demande de ne plus être contacté (RGPD) : bloque tout envoi et toute tâche. */
    #[ORM\Column]
    private bool $optOut = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\ManyToOne(inversedBy: 'prospects')]
    private ?Prescripteur $prescripteur = null;

    #[ORM\Column]
    private \DateTimeImmutable $creeLe;

    /** @var Collection<int, Contact> */
    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'prospect', cascade: ['persist'], orphanRemoval: true)]
    private Collection $contacts;

    /** @var Collection<int, Interaction> */
    #[ORM\OneToMany(targetEntity: Interaction::class, mappedBy: 'prospect', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['date' => 'DESC'])]
    private Collection $interactions;

    /** @var Collection<int, Tache> */
    #[ORM\OneToMany(targetEntity: Tache::class, mappedBy: 'prospect', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['echeance' => 'ASC'])]
    private Collection $taches;

    /** @var Collection<int, Devis> */
    #[ORM\OneToMany(targetEntity: Devis::class, mappedBy: 'prospect', cascade: ['persist'])]
    private Collection $devis;

    public function __construct(string $nom, ?\DateTimeImmutable $creeLe = null)
    {
        $this->nom = $nom;
        $this->creeLe = $creeLe ?? new \DateTimeImmutable();
        $this->contacts = new ArrayCollection();
        $this->interactions = new ArrayCollection();
        $this->taches = new ArrayCollection();
        $this->devis = new ArrayCollection();
    }

    /**
     * Calculée à partir des trois critères, selon la définition de la base Notion :
     * froid = contacté sans besoin qualifié, tiède = besoin identifié,
     * chaud = besoin + capacité confirmés et intention affirmée.
     * Null tant que le prospect n'a jamais été contacté.
     */
    public function getTemperature(): ?Temperature
    {
        if (Statut::A_CONTACTER === $this->getStatutEnum() && null === $this->dernierContact) {
            return null;
        }
        if ($this->besoinIdentifie && $this->capaciteBudget && $this->intentionAffirmee) {
            return Temperature::CHAUD;
        }
        if ($this->besoinIdentifie) {
            return Temperature::TIEDE;
        }

        return Temperature::FROID;
    }

    /** @return list<Tache> */
    public function getTachesOuvertes(): array
    {
        return array_values($this->taches->filter(static fn (Tache $t) => $t->estOuverte())->toArray());
    }

    public function ajouterTache(Tache $tache): static
    {
        if (!$this->taches->contains($tache)) {
            $this->taches->add($tache);
        }

        return $this;
    }

    public function annulerTachesOuvertes(\DateTimeImmutable $le): void
    {
        foreach ($this->getTachesOuvertes() as $tache) {
            $tache->annuler($le);
        }
    }

    public function ajouterInteraction(Interaction $interaction): static
    {
        if (!$this->interactions->contains($interaction)) {
            $this->interactions->add($interaction);
        }

        return $this;
    }

    public function ajouterContact(Contact $contact): static
    {
        if (!$this->contacts->contains($contact)) {
            $this->contacts->add($contact);
        }

        return $this;
    }

    public function getContactPrincipal(): ?Contact
    {
        return $this->contacts->first() ?: null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getSecteur(): ?Secteur
    {
        return $this->secteur;
    }

    public function setSecteur(?Secteur $secteur): static
    {
        $this->secteur = $secteur;

        return $this;
    }

    public function getSource(): ?Source
    {
        return $this->source;
    }

    public function setSource(?Source $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getSiteActuel(): SiteActuel
    {
        return $this->siteActuel;
    }

    public function setSiteActuel(SiteActuel $siteActuel): static
    {
        $this->siteActuel = $siteActuel;

        return $this;
    }

    public function getUrlSite(): ?string
    {
        return $this->urlSite;
    }

    public function setUrlSite(?string $urlSite): static
    {
        $this->urlSite = $urlSite;

        return $this;
    }

    public function getPriorite(): Priorite
    {
        return $this->priorite;
    }

    public function setPriorite(Priorite $priorite): static
    {
        $this->priorite = $priorite;

        return $this;
    }

    public function getOffreVisee(): Offre
    {
        return $this->offreVisee;
    }

    public function setOffreVisee(Offre $offreVisee): static
    {
        $this->offreVisee = $offreVisee;

        return $this;
    }

    public function getCanalPrefere(): ?Canal
    {
        return $this->canalPrefere;
    }

    public function setCanalPrefere(?Canal $canalPrefere): static
    {
        $this->canalPrefere = $canalPrefere;

        return $this;
    }

    /** Utilisé par le marking store du workflow. */
    public function getStatut(): string
    {
        return $this->statut;
    }

    /** Utilisé par le marking store du workflow : passer par les transitions, pas par ce setter. */
    public function setStatut(string $statut, array $context = []): static
    {
        $this->statut = Statut::from($statut)->value;

        return $this;
    }

    public function getStatutEnum(): Statut
    {
        return Statut::from($this->statut);
    }

    public function isBesoinIdentifie(): bool
    {
        return $this->besoinIdentifie;
    }

    public function setBesoinIdentifie(bool $besoinIdentifie): static
    {
        $this->besoinIdentifie = $besoinIdentifie;

        return $this;
    }

    public function isCapaciteBudget(): bool
    {
        return $this->capaciteBudget;
    }

    public function setCapaciteBudget(bool $capaciteBudget): static
    {
        $this->capaciteBudget = $capaciteBudget;

        return $this;
    }

    public function isIntentionAffirmee(): bool
    {
        return $this->intentionAffirmee;
    }

    public function setIntentionAffirmee(bool $intentionAffirmee): static
    {
        $this->intentionAffirmee = $intentionAffirmee;

        return $this;
    }

    public function getNbRelances(): int
    {
        return $this->nbRelances;
    }

    public function setNbRelances(int $nbRelances): static
    {
        $this->nbRelances = $nbRelances;

        return $this;
    }

    public function getDernierContact(): ?\DateTimeImmutable
    {
        return $this->dernierContact;
    }

    public function setDernierContact(?\DateTimeImmutable $dernierContact): static
    {
        $this->dernierContact = $dernierContact;

        return $this;
    }

    public function isOptOut(): bool
    {
        return $this->optOut;
    }

    public function setOptOut(bool $optOut): static
    {
        $this->optOut = $optOut;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getPrescripteur(): ?Prescripteur
    {
        return $this->prescripteur;
    }

    public function setPrescripteur(?Prescripteur $prescripteur): static
    {
        $this->prescripteur = $prescripteur;

        return $this;
    }

    public function getCreeLe(): \DateTimeImmutable
    {
        return $this->creeLe;
    }

    /** @return Collection<int, Contact> */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    /** @return Collection<int, Interaction> */
    public function getInteractions(): Collection
    {
        return $this->interactions;
    }

    /** @return Collection<int, Tache> */
    public function getTaches(): Collection
    {
        return $this->taches;
    }

    /** @return Collection<int, Devis> */
    public function getDevis(): Collection
    {
        return $this->devis;
    }
}
