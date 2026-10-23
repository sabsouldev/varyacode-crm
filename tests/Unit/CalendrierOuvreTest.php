<?php

namespace App\Tests\Unit;

use App\Prospection\CalendrierOuvre;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CalendrierOuvreTest extends TestCase
{
    #[DataProvider('cas')]
    public function testAjouterJoursOuvres(string $depart, int $jours, string $attendu): void
    {
        $resultat = (new CalendrierOuvre())->ajouterJoursOuvres(new \DateTimeImmutable($depart), $jours);

        self::assertSame($attendu, $resultat->format('Y-m-d'));
    }

    public static function cas(): iterable
    {
        yield 'en semaine' => ['2026-10-19', 3, '2026-10-22'];
        yield 'saute le week-end' => ['2026-10-23', 5, '2026-10-30'];
        yield 'saute le 11 novembre' => ['2026-11-09', 3, '2026-11-13'];
        yield 'saute le lundi de Pâques 2027' => ['2027-03-26', 1, '2027-03-30'];
        yield 'saute l\'Ascension 2027' => ['2027-05-05', 1, '2027-05-07'];
    }

    public function testFeries(): void
    {
        $calendrier = new CalendrierOuvre();

        self::assertTrue($calendrier->estFerie(new \DateTimeImmutable('2027-05-17'))); // lundi de Pentecôte
        self::assertTrue($calendrier->estFerie(new \DateTimeImmutable('2026-12-25')));
        self::assertFalse($calendrier->estFerie(new \DateTimeImmutable('2026-10-23')));
    }
}
