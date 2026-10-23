<?php

namespace App\Prospection;

/**
 * Noms des transitions du workflow "prospect" (config/packages/workflow.yaml).
 */
final class Transition
{
    public const PREMIER_CONTACT = 'premier_contact';
    public const ENVOYER_MESSAGE = 'envoyer_message';
    public const RELANCER = 'relancer';
    public const FIXER_RDV = 'fixer_rdv';
    public const ENVOYER_DEVIS = 'envoyer_devis';
    public const GAGNER = 'gagner';
    public const PERDRE = 'perdre';
    public const METTRE_EN_PAUSE = 'mettre_en_pause';
    public const REPRENDRE = 'reprendre';

    /** Transitions qui sollicitent le prospect : bloquées par l'opt-out et le quota Club IVS. */
    public const SOLLICITATIONS = [self::PREMIER_CONTACT, self::ENVOYER_MESSAGE, self::RELANCER];
}
