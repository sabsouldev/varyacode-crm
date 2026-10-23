<?php

namespace App\Enum;

enum TypeInteraction: string
{
    case MESSAGE = 'message';
    case APPEL = 'appel';
    case RENDEZ_VOUS = 'rendez_vous';
    case AUDIT = 'audit';
    case REPONSE = 'reponse';
    case NOTE = 'note';
}
