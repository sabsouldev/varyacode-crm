<?php

namespace App\Enum;

/**
 * "Vérifier" et "envoyer" sont volontairement séparés : la vérification se fait
 * seule et sans enjeu, l'envoi part d'un message déjà rédigé.
 */
enum TypeTache: string
{
    case VERIFIER = 'verifier';
    case ENVOYER = 'envoyer';
    case APPELER = 'appeler';
    case RELANCER = 'relancer';
    case RELANCER_DEVIS = 'relancer_devis';
    case CLOTURER_SANS_REPONSE = 'cloturer_sans_reponse';
    case REPRENDRE_CONTACT = 'reprendre_contact';
    case ENQUETE_SATISFACTION = 'enquete_satisfaction';
    case BILAN_MOIS_10 = 'bilan_mois_10';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::VERIFIER => 'Vérifier site et coordonnées',
            self::ENVOYER => 'Envoyer le message',
            self::APPELER => 'Appeler',
            self::RELANCER => 'Relancer',
            self::RELANCER_DEVIS => 'Relancer le devis',
            self::CLOTURER_SANS_REPONSE => 'Passer en « Pas maintenant » sans réponse',
            self::REPRENDRE_CONTACT => 'Reprendre contact',
            self::ENQUETE_SATISFACTION => 'Enquête de satisfaction (6 mois)',
            self::BILAN_MOIS_10 => 'Bilan du mois 10',
            self::AUTRE => 'Autre',
        };
    }
}
