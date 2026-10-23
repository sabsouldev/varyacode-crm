<?php

namespace App\Enum;

/**
 * Étapes du pipeline. Les valeurs servent de "places" au workflow "prospect".
 */
enum Statut: string
{
    case A_CONTACTER = 'a_contacter';
    case CONTACTE = 'contacte';
    case EN_ATTENTE = 'en_attente';
    case RDV_FIXE = 'rdv_fixe';
    case DEVIS_ENVOYE = 'devis_envoye';
    case GAGNE = 'gagne';
    case PERDU = 'perdu';
    case PAS_MAINTENANT = 'pas_maintenant';

    public function label(): string
    {
        return match ($this) {
            self::A_CONTACTER => 'À contacter',
            self::CONTACTE => 'Contacté',
            self::EN_ATTENTE => 'En attente de réponse',
            self::RDV_FIXE => 'RDV fixé',
            self::DEVIS_ENVOYE => 'Devis envoyé',
            self::GAGNE => 'Gagné',
            self::PERDU => 'Perdu',
            self::PAS_MAINTENANT => 'Pas maintenant',
        };
    }

    public function estClos(): bool
    {
        return \in_array($this, [self::GAGNE, self::PERDU], true);
    }
}
