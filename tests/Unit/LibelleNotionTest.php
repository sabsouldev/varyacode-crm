<?php

namespace App\Tests\Unit;

use App\Enum\Priorite;
use App\Enum\Secteur;
use App\Enum\Statut;
use App\Import\LibelleNotion;
use PHPUnit\Framework\TestCase;

final class LibelleNotionTest extends TestCase
{
    public function testRetrouveLesLibellesNotion(): void
    {
        self::assertSame(Priorite::HAUTE, LibelleNotion::versEnum(Priorite::class, '🔥 Haute'));
        self::assertSame(Statut::A_CONTACTER, LibelleNotion::versEnum(Statut::class, 'À contacter'));
        self::assertSame(Statut::EN_ATTENTE, LibelleNotion::versEnum(Statut::class, 'En attente de réponse'));
        self::assertSame(Secteur::BIEN_ETRE, LibelleNotion::versEnum(Secteur::class, 'Bien-être / accompagnement'));
    }

    public function testLibelleInconnuOuVide(): void
    {
        self::assertNull(LibelleNotion::versEnum(Statut::class, 'Archivé'));
        self::assertNull(LibelleNotion::versEnum(Statut::class, ''));
        self::assertNull(LibelleNotion::versEnum(Statut::class, null));
    }
}
