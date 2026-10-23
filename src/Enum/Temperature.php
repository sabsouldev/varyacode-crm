<?php

namespace App\Enum;

enum Temperature: string
{
    case FROID = 'froid';
    case TIEDE = 'tiede';
    case CHAUD = 'chaud';

    public function label(): string
    {
        return match ($this) {
            self::FROID => '❄️ Froid — suspect',
            self::TIEDE => '🌤️ Tiède — lead',
            self::CHAUD => '🔥 Chaud — prospect',
        };
    }
}
