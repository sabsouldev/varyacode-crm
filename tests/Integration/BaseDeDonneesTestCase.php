<?php

namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

abstract class BaseDeDonneesTestCase extends KernelTestCase
{
    protected EntityManagerInterface $em;
    protected MockClock $clock;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->clock = new MockClock('2026-10-26 09:00:00', 'Europe/Paris'); // un lundi
        static::getContainer()->set('clock', $this->clock);

        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $schema = new SchemaTool($this->em);
        $metadonnees = $this->em->getMetadataFactory()->getAllMetadata();
        $schema->dropSchema($metadonnees);
        $schema->createSchema($metadonnees);
    }

    protected function service(string $classe): object
    {
        return static::getContainer()->get($classe);
    }
}
