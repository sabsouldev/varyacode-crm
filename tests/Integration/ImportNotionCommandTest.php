<?php

namespace App\Tests\Integration;

use App\Enum\Priorite;
use App\Enum\Source;
use App\Enum\Statut;
use App\Enum\Temperature;
use App\Enum\TypeTache;
use App\Repository\ProspectRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class ImportNotionCommandTest extends BaseDeDonneesTestCase
{
    public function testImporteLExportNotion(): void
    {
        $commande = $this->commande();
        $commande->execute(['fichier' => __DIR__.'/../fixtures/notion_prospects.csv']);

        $commande->assertCommandIsSuccessful();
        self::assertStringContainsString('3 prospect(s) importé(s), 2 ignoré(s)', $commande->getDisplay());

        $prospects = $this->service(ProspectRepository::class);
        self::assertCount(3, $prospects->findAll());

        $caroline = $prospects->findOneBy(['nom' => 'Caroline Coach']);
        self::assertSame(Statut::EN_ATTENTE, $caroline->getStatutEnum());
        self::assertSame(Priorite::HAUTE, $caroline->getPriorite());
        self::assertSame(Source::CLUB_IVS, $caroline->getSource());
        self::assertSame(Temperature::TIEDE, $caroline->getTemperature());
        self::assertSame(1, $caroline->getNbRelances());
        self::assertSame('2026-09-28', $caroline->getDernierContact()->format('Y-m-d'));
        self::assertSame('caroline@example.com', $caroline->getContactPrincipal()->getEmail());

        $tache = $caroline->getTachesOuvertes()[0];
        self::assertSame(TypeTache::RELANCER, $tache->getType());
        self::assertSame('2026-10-20', $tache->getEcheance()->format('Y-m-d'));
        self::assertSame('Relance avec 1-2 remarques concrètes sur son site', $tache->getDescription());

        $ricochets = $prospects->findOneBy(['nom' => 'Association Ricochets']);
        self::assertSame(TypeTache::VERIFIER, $ricochets->getTachesOuvertes()[0]->getType());
        self::assertSame('05 53 00 00 00', $ricochets->getContactPrincipal()->getTelephone());

        self::assertSame([], $prospects->findOneBy(['nom' => 'LUSgem'])->getTachesOuvertes(), 'un prospect gagné n\'a pas de tâche de prospection');
    }

    public function testDryRunNEnregistreRien(): void
    {
        $commande = $this->commande();
        $commande->execute(['fichier' => __DIR__.'/../fixtures/notion_prospects.csv', '--dry-run' => true]);

        self::assertStringContainsString('3 prospect(s) à importer', $commande->getDisplay());
        self::assertSame([], $this->service(ProspectRepository::class)->findAll());
    }

    private function commande(): CommandTester
    {
        return new CommandTester((new Application(self::$kernel))->find('app:import:notion'));
    }
}
