<?php

namespace App\Enum;

enum SiteActuel: string
{
    case A_VERIFIER = 'a_verifier';
    case AUCUN = 'aucun';
    case A_REFAIRE = 'a_refaire';
    case CORRECT = 'correct';

    public function label(): string
    {
        return match ($this) {
            self::A_VERIFIER => 'À vérifier',
            self::AUCUN => 'Pas de site',
            self::A_REFAIRE => 'Site à refaire',
            self::CORRECT => 'Site correct',
        };
    }
}
