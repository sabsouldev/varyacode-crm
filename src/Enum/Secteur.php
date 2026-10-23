<?php

namespace App\Enum;

enum Secteur: string
{
    case ASSOCIATION = 'association';
    case BIEN_ETRE = 'bien_etre';
    case COMMERCE = 'commerce';
    case ARTISAN = 'artisan';
    case INDEPENDANT = 'independant';
    case COLLECTIVITE = 'collectivite';
    case AUTRE = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::ASSOCIATION => 'Association',
            self::BIEN_ETRE => 'Bien-être / accompagnement',
            self::COMMERCE => 'Commerce / créateur',
            self::ARTISAN => 'Artisan',
            self::INDEPENDANT => 'Indépendant / services',
            self::COLLECTIVITE => 'Collectivité',
            self::AUTRE => 'Autre',
        };
    }
}
