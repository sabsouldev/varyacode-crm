<?php

namespace App\Enum;

enum Offre: string
{
    case VITRINE = 'vitrine';
    case PILOTE_AUTOMATIQUE = 'pilote_automatique';
    case DECOLLAGE = 'decollage';
    case MAINTENANCE = 'maintenance';
    case A_DEFINIR = 'a_definir';

    public function label(): string
    {
        return match ($this) {
            self::VITRINE => 'Vitrine',
            self::PILOTE_AUTOMATIQUE => 'Pilote Automatique',
            self::DECOLLAGE => 'Décollage',
            self::MAINTENANCE => 'Maintenance',
            self::A_DEFINIR => 'À définir',
        };
    }

    /** Mensualité TTC avec engagement 12 mois, en centimes. */
    public function mensualite(): ?int
    {
        return match ($this) {
            self::VITRINE => 4900,
            self::PILOTE_AUTOMATIQUE => 7900,
            self::DECOLLAGE => 10900,
            default => null,
        };
    }

    /** Frais de mise en ligne facturés une fois à la signature, en centimes. */
    public function fraisMiseEnLigne(): ?int
    {
        return match ($this) {
            self::VITRINE => 15000,
            self::PILOTE_AUTOMATIQUE => 25000,
            self::DECOLLAGE => 35000,
            default => null,
        };
    }
}
