<?php

namespace App\Prospection;

/**
 * Jours ouvrés en France : hors week-ends et jours fériés nationaux.
 */
final class CalendrierOuvre
{
    public function ajouterJoursOuvres(\DateTimeImmutable $depart, int $jours): \DateTimeImmutable
    {
        $date = $depart->setTime(0, 0);
        while ($jours > 0) {
            $date = $date->modify('+1 day');
            if ($this->estOuvre($date)) {
                --$jours;
            }
        }

        return $date;
    }

    public function estOuvre(\DateTimeImmutable $date): bool
    {
        return (int) $date->format('N') < 6 && !$this->estFerie($date);
    }

    public function estFerie(\DateTimeImmutable $date): bool
    {
        return \in_array($date->format('Y-m-d'), $this->joursFeries((int) $date->format('Y')), true);
    }

    /** @return list<string> */
    private function joursFeries(int $annee): array
    {
        $paques = $this->paques($annee);

        return [
            "$annee-01-01",
            $paques->modify('+1 day')->format('Y-m-d'),  // lundi de Pâques
            "$annee-05-01",
            "$annee-05-08",
            $paques->modify('+39 days')->format('Y-m-d'), // Ascension
            $paques->modify('+50 days')->format('Y-m-d'), // lundi de Pentecôte
            "$annee-07-14",
            "$annee-08-15",
            "$annee-11-01",
            "$annee-11-11",
            "$annee-12-25",
        ];
    }

    /** Dimanche de Pâques (algorithme de Meeus / Jones / Butcher). */
    private function paques(int $annee): \DateTimeImmutable
    {
        $a = $annee % 19;
        $b = intdiv($annee, 100);
        $c = $annee % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mois = intdiv($h + $l - 7 * $m + 114, 31);
        $jour = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new \DateTimeImmutable(\sprintf('%d-%02d-%02d', $annee, $mois, $jour));
    }
}
