<?php

namespace App\Enum;

enum Source: string
{
    case CLUB_IVS = 'club_ivs';
    case FICHIER = 'fichier';
    case LEA = 'lea';
    case SITE_WEB = 'site_web';
    case RESEAU = 'reseau';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::CLUB_IVS => 'Club IVS',
            self::FICHIER => 'Fichier prospection',
            self::LEA => 'Léa',
            self::SITE_WEB => 'Site web',
            self::RESEAU => 'Réseau',
            self::AUTRE => 'Autre',
        };
    }
}
