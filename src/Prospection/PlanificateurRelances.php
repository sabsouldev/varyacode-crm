<?php

namespace App\Prospection;

use App\Entity\Prospect;
use App\Entity\Tache;
use App\Enum\TypeTache;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\CompletedEvent;

/**
 * Crée la prochaine tâche après chaque transition, pour qu'une fiche en cours
 * ne reste jamais sans prochaine date.
 */
final class PlanificateurRelances implements EventSubscriberInterface
{
    public function __construct(
        private readonly ReglesRelance $regles,
        private readonly CalendrierOuvre $calendrier,
        private readonly ClockInterface $clock,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return ['workflow.prospect.completed' => 'apresTransition'];
    }

    /** À appeler à la création d'un prospect : la première étape est toujours la vérification. */
    public function initialiser(Prospect $prospect): void
    {
        if (!$prospect->getTachesOuvertes()) {
            new Tache($prospect, TypeTache::VERIFIER, $this->aujourdhui(), null, true);
        }
    }

    public function apresTransition(CompletedEvent $event): void
    {
        /** @var Prospect $prospect */
        $prospect = $event->getSubject();
        $context = $event->getContext();
        $aujourdhui = $this->aujourdhui();

        match ($event->getTransition()?->getName()) {
            Transition::PREMIER_CONTACT, Transition::ENVOYER_MESSAGE => $this->demarrerSequence($prospect, $aujourdhui),
            Transition::RELANCER => $this->relanceFaite($prospect, $aujourdhui),
            Transition::FIXER_RDV => $this->remplacer($prospect, TypeTache::AUTRE, $this->dateRdv($context) ?? $this->calendrier->ajouterJoursOuvres($aujourdhui, 5), 'Rendez-vous découverte : qualifier besoin et budget'),
            Transition::ENVOYER_DEVIS => $this->planifierRelancesDevis($prospect, $aujourdhui),
            Transition::GAGNER => $this->planifierFidelisation($prospect, $aujourdhui),
            Transition::PERDRE => $prospect->annulerTachesOuvertes($this->clock->now()),
            Transition::METTRE_EN_PAUSE => $this->remplacer($prospect, TypeTache::REPRENDRE_CONTACT, $aujourdhui->modify(\sprintf('+%d months', $this->regles->pauseMois))),
            Transition::REPRENDRE => $this->remplacer($prospect, TypeTache::VERIFIER, $aujourdhui),
            default => null,
        };
    }

    private function demarrerSequence(Prospect $prospect, \DateTimeImmutable $aujourdhui): void
    {
        $prospect->setDernierContact($aujourdhui)->setNbRelances(0);
        $this->terminerOuvertes($prospect);
        $this->planifierRelanceMessage($prospect, $aujourdhui, 0);
    }

    private function relanceFaite(Prospect $prospect, \DateTimeImmutable $aujourdhui): void
    {
        $prospect->setDernierContact($aujourdhui)->setNbRelances($prospect->getNbRelances() + 1);
        $this->terminerOuvertes($prospect);
        $this->planifierRelanceMessage($prospect, $aujourdhui, $prospect->getNbRelances());
    }

    /**
     * Les décalages sont comptés depuis le premier envoi : J+5 puis J+10 deviennent
     * +5 puis +5 jours ouvrés. Après la dernière relance, le même écart sépare de la
     * mise en pause automatique.
     */
    private function planifierRelanceMessage(Prospect $prospect, \DateTimeImmutable $aujourdhui, int $relancesFaites): void
    {
        $decalages = $this->regles->relancesMessageJoursOuvres;
        $precedent = $decalages[$relancesFaites - 1] ?? 0;

        if ($relancesFaites < \count($decalages)) {
            $ecart = $decalages[$relancesFaites] - $precedent;
            new Tache($prospect, TypeTache::RELANCER, $this->calendrier->ajouterJoursOuvres($aujourdhui, $ecart), \sprintf('Relance %d sur %d', $relancesFaites + 1, \count($decalages)), true);

            return;
        }

        $ecart = $precedent - ($decalages[$relancesFaites - 2] ?? 0);
        new Tache($prospect, TypeTache::CLOTURER_SANS_REPONSE, $this->calendrier->ajouterJoursOuvres($aujourdhui, $ecart), null, true);
    }

    private function planifierRelancesDevis(Prospect $prospect, \DateTimeImmutable $aujourdhui): void
    {
        $prospect->annulerTachesOuvertes($this->clock->now());
        foreach ($this->regles->relancesDevisJours as $jours) {
            new Tache($prospect, TypeTache::RELANCER_DEVIS, $aujourdhui->modify(\sprintf('+%d days', $jours)), \sprintf('Relance devis J+%d', $jours), true);
        }
    }

    private function planifierFidelisation(Prospect $prospect, \DateTimeImmutable $aujourdhui): void
    {
        $prospect->annulerTachesOuvertes($this->clock->now());
        new Tache($prospect, TypeTache::ENQUETE_SATISFACTION, $aujourdhui->modify('+6 months'), null, true);
        new Tache($prospect, TypeTache::BILAN_MOIS_10, $aujourdhui->modify('+10 months'), 'Résultats de l\'année et proposition d\'évolution avant la fin de l\'engagement', true);
    }

    private function remplacer(Prospect $prospect, TypeTache $type, \DateTimeImmutable $echeance, ?string $description = null): void
    {
        $prospect->annulerTachesOuvertes($this->clock->now());
        new Tache($prospect, $type, $echeance, $description, true);
    }

    /** Les tâches ouvertes au moment d'un contact sont celles que ce contact vient d'accomplir. */
    private function terminerOuvertes(Prospect $prospect): void
    {
        foreach ($prospect->getTachesOuvertes() as $tache) {
            $tache->marquerFaite($this->clock->now());
        }
    }

    private function dateRdv(array $context): ?\DateTimeImmutable
    {
        $date = $context['date_rdv'] ?? null;

        return $date instanceof \DateTimeImmutable ? $date : null;
    }

    private function aujourdhui(): \DateTimeImmutable
    {
        return $this->clock->now()->setTime(0, 0);
    }
}
