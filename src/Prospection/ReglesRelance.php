<?php

namespace App\Prospection;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Délais de relance, réglables dans config/services.yaml sans toucher au code.
 */
final class ReglesRelance
{
    /**
     * @param list<int> $relancesMessageJoursOuvres décalages depuis le premier envoi (J+5 puis J+10)
     * @param list<int> $relancesDevisJours         décalages calendaires depuis l'envoi du devis (J+3 puis J+7)
     */
    public function __construct(
        #[Autowire('%prospection.relances_message_jours_ouvres%')]
        public readonly array $relancesMessageJoursOuvres,
        #[Autowire('%prospection.relances_devis_jours%')]
        public readonly array $relancesDevisJours,
        #[Autowire('%prospection.pause_mois%')]
        public readonly int $pauseMois,
        #[Autowire('%prospection.quota_club_ivs_semaine%')]
        public readonly int $quotaClubIvsParSemaine,
    ) {
    }

    public function nbRelancesMessage(): int
    {
        return \count($this->relancesMessageJoursOuvres);
    }
}
