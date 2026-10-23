<?php

namespace App\Prospection;

use App\Entity\Prospect;
use App\Enum\Source;
use App\Enum\Statut;
use App\Repository\InteractionRepository;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\GuardEvent;
use Symfony\Component\Workflow\TransitionBlocker;

/**
 * Bloque les sollicitations interdites : prospect opposé (RGPD) et quota
 * hebdomadaire de nouveaux contacts Club IVS dépassé.
 */
final class GardeSollicitation implements EventSubscriberInterface
{
    public const CODE_OPT_OUT = 'opt_out';
    public const CODE_QUOTA_CLUB_IVS = 'quota_club_ivs';

    public function __construct(
        private readonly InteractionRepository $interactions,
        private readonly ReglesRelance $regles,
        private readonly ClockInterface $clock,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        $evenements = [];
        foreach (Transition::SOLLICITATIONS as $transition) {
            $evenements['workflow.prospect.guard.'.$transition] = 'verifier';
        }

        return $evenements;
    }

    public function verifier(GuardEvent $event): void
    {
        /** @var Prospect $prospect */
        $prospect = $event->getSubject();

        if ($prospect->isOptOut()) {
            $event->addTransitionBlocker(new TransitionBlocker('Ce prospect a demandé à ne plus être contacté.', self::CODE_OPT_OUT));

            return;
        }

        if (Source::CLUB_IVS === $prospect->getSource() && $this->estNouveauContact($prospect) && $this->quotaClubIvsAtteint()) {
            $event->addTransitionBlocker(new TransitionBlocker(
                \sprintf('Quota Club IVS atteint : %d nouveaux contacts maximum par semaine.', $this->regles->quotaClubIvsParSemaine),
                self::CODE_QUOTA_CLUB_IVS,
            ));
        }
    }

    private function estNouveauContact(Prospect $prospect): bool
    {
        return \in_array($prospect->getStatutEnum(), [Statut::A_CONTACTER, Statut::PAS_MAINTENANT], true);
    }

    private function quotaClubIvsAtteint(): bool
    {
        $lundi = $this->clock->now()->modify('monday this week')->setTime(0, 0);

        return $this->interactions->compterProspectsContactes($lundi, $lundi->modify('+7 days'), Source::CLUB_IVS) >= $this->regles->quotaClubIvsParSemaine;
    }
}
