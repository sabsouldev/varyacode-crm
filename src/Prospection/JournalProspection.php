<?php

namespace App\Prospection;

use App\Entity\Interaction;
use App\Entity\Prospect;
use App\Enum\Canal;
use App\Enum\TypeInteraction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Point d'entrée unique pour faire avancer un prospect : applique la transition,
 * journalise l'échange et laisse le planificateur créer la tâche suivante.
 */
final class JournalProspection
{
    public function __construct(
        #[Target('prospect')]
        private readonly WorkflowInterface $workflow,
        private readonly EntityManagerInterface $em,
        private readonly ClockInterface $clock,
        private readonly PlanificateurRelances $planificateur,
    ) {
    }

    public function nouveauProspect(Prospect $prospect): void
    {
        $this->planificateur->initialiser($prospect);
        $this->em->persist($prospect);
        $this->em->flush();
    }

    /**
     * @param array<string, mixed> $context par exemple ['date_rdv' => DateTimeImmutable]
     *
     * @throws SollicitationRefusee si la transition est bloquée (opt-out, quota, statut incompatible)
     */
    public function enregistrer(Prospect $prospect, string $transition, ?Canal $canal = null, ?string $resume = null, array $context = []): void
    {
        $blocages = $this->workflow->buildTransitionBlockerList($prospect, $transition);
        if (!$blocages->isEmpty()) {
            $raisons = [];
            foreach ($blocages as $blocage) {
                $raisons[] = $blocage->getMessage();
            }
            throw new SollicitationRefusee($raisons);
        }

        $this->workflow->apply($prospect, $transition, $context);
        new Interaction($prospect, $this->clock->now(), $this->typeInteraction($transition, $canal), $canal, $resume);
        $this->em->flush();
    }

    private function typeInteraction(string $transition, ?Canal $canal): TypeInteraction
    {
        if (!\in_array($transition, Transition::SOLLICITATIONS, true)) {
            return Transition::FIXER_RDV === $transition ? TypeInteraction::REPONSE : TypeInteraction::NOTE;
        }

        return match ($canal) {
            Canal::TELEPHONE => TypeInteraction::APPEL,
            Canal::EN_PERSONNE => TypeInteraction::RENDEZ_VOUS,
            default => TypeInteraction::MESSAGE,
        };
    }
}
