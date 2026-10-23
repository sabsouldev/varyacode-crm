<?php

namespace App\Tests\Unit;

use App\Entity\Prospect;
use App\Enum\Temperature;
use PHPUnit\Framework\TestCase;

final class ProspectTest extends TestCase
{
    public function testPasDeTemperatureAvantLePremierContact(): void
    {
        self::assertNull((new Prospect('Caroline Coach'))->getTemperature());
    }

    public function testTemperatureCalculeeDepuisLesTroisCriteres(): void
    {
        $prospect = (new Prospect('Caroline Coach'))->setDernierContact(new \DateTimeImmutable('2026-10-20'));
        self::assertSame(Temperature::FROID, $prospect->getTemperature());

        $prospect->setBesoinIdentifie(true);
        self::assertSame(Temperature::TIEDE, $prospect->getTemperature());

        $prospect->setCapaciteBudget(true);
        self::assertSame(Temperature::TIEDE, $prospect->getTemperature(), 'chaud exige aussi une intention affirmée');

        $prospect->setIntentionAffirmee(true);
        self::assertSame(Temperature::CHAUD, $prospect->getTemperature());
    }
}
