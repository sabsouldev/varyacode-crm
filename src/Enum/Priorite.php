<?php

namespace App\Enum;

enum Priorite: string
{
    case HAUTE = 'haute';
    case MOYENNE = 'moyenne';
    case BASSE = 'basse';

    public function label(): string
    {
        return match ($this) {
            self::HAUTE => '🔥 Haute',
            self::MOYENNE => 'Moyenne',
            self::BASSE => 'Basse',
        };
    }
}
