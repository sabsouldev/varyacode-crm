<?php

namespace App\Enum;

enum Canal: string
{
    case MAIL = 'mail';
    case TELEPHONE = 'telephone';
    case EN_PERSONNE = 'en_personne';
    case RESEAU_SOCIAL = 'reseau_social';

    public function label(): string
    {
        return match ($this) {
            self::MAIL => 'Mail',
            self::TELEPHONE => 'Téléphone',
            self::EN_PERSONNE => 'En personne',
            self::RESEAU_SOCIAL => 'Réseau social',
        };
    }
}
