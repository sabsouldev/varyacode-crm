<?php

namespace App\Tests\Integration;

use App\Entity\Prospect;
use App\Entity\Tache;
use App\Enum\Canal;
use App\Enum\Source;
use App\Enum\Statut;
use App\Enum\TypeTache;
use App\Prospection\JournalProspection;
use App\Prospection\SollicitationRefusee;
use App\Prospection\Transition;
use App\Repository\ProspectRepository;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class ParcoursProspectTest extends BaseDeDonneesTestCase
{
    private JournalProspection $journal;

    protected function setUp(): void
    {
        parent::setUp();
        $this->journal = $this->service(JournalProspection::class);
    }

    public function testUnNouveauProspectCommenceParUneVerification(): void
    {
        $prospect = new Prospect('Hygge en Périgord');
        $this->journal->nouveauProspect($prospect);

        self::assertSame([TypeTache::VERIFIER], $this->typesOuverts($prospect));
        self::assertSame('2026-10-26', $prospect->getTachesOuvertes()[0]->getEcheance()->format('Y-m-d'));
    }

    public function testSequenceDeRelancesPuisMiseEnPauseAutomatique(): void
    {
        $prospect = new Prospect('Educ et Moi');
        $this->journal->nouveauProspect($prospect);

        // Lundi 26/10 : mail envoyé → relance à J+5 ouvrés.
        $this->journal->enregistrer($prospect, Transition::ENVOYER_MESSAGE, Canal::MAIL);
        self::assertSame(Statut::EN_ATTENTE, $prospect->getStatutEnum());
        self::assertSame(['relancer' => '2026-11-02'], $this->echeances($prospect));

        // Lundi 02/11 : 1re relance → 2e à J+10 du premier envoi, soit +5 ouvrés.
        $this->clock->modify('2026-11-02 10:00');
        $this->journal->enregistrer($prospect, Transition::RELANCER, Canal::MAIL);
        self::assertSame(1, $prospect->getNbRelances());
        self::assertSame(['relancer' => '2026-11-09'], $this->echeances($prospect));

        // Lundi 09/11 : 2e relance → mise en pause prévue 5 jours ouvrés plus tard (11/11 férié).
        $this->clock->modify('2026-11-09 10:00');
        $this->journal->enregistrer($prospect, Transition::RELANCER, Canal::MAIL);
        self::assertSame(['cloturer_sans_reponse' => '2026-11-17'], $this->echeances($prospect));

        // Mardi 17/11 : la tâche planifiée passe le prospect en « Pas maintenant » pour 3 mois.
        $this->clock->modify('2026-11-17 07:00');
        $commande = new CommandTester((new Application(self::$kernel))->find('app:prospection:cloturer-sans-reponse'));
        $commande->execute([]);

        $commande->assertCommandIsSuccessful();
        self::assertSame(Statut::PAS_MAINTENANT, $prospect->getStatutEnum());
        self::assertSame(['reprendre_contact' => '2027-02-17'], $this->echeances($prospect));
    }

    public function testUnDevisPlanifieLesRelancesJ3EtJ7(): void
    {
        $prospect = new Prospect('Caroline Coach');
        $this->journal->nouveauProspect($prospect);
        $this->journal->enregistrer($prospect, Transition::PREMIER_CONTACT, Canal::TELEPHONE);
        $this->journal->enregistrer($prospect, Transition::FIXER_RDV, null, null, ['date_rdv' => new \DateTimeImmutable('2026-10-29')]);
        self::assertSame(['autre' => '2026-10-29'], $this->echeances($prospect));

        $this->journal->enregistrer($prospect, Transition::ENVOYER_DEVIS);

        $relances = array_map(static fn (Tache $t) => $t->getEcheance()->format('Y-m-d'), $prospect->getTachesOuvertes());
        self::assertSame(['2026-10-29', '2026-11-02'], $relances);
    }

    public function testGagnerPlanifieEnqueteEtBilan(): void
    {
        $prospect = new Prospect('Florence Pelé – naturopathe');
        $prospect->setStatut(Statut::DEVIS_ENVOYE->value);
        $this->journal->nouveauProspect($prospect);

        $this->journal->enregistrer($prospect, Transition::GAGNER);

        self::assertSame(['enquete_satisfaction' => '2027-04-26', 'bilan_mois_10' => '2027-08-26'], $this->echeances($prospect));
    }

    public function testOptOutBloqueToutEnvoi(): void
    {
        $prospect = (new Prospect('Electrochoc'))->setOptOut(true);
        $this->journal->nouveauProspect($prospect);

        $this->expectException(SollicitationRefusee::class);
        $this->journal->enregistrer($prospect, Transition::ENVOYER_MESSAGE, Canal::MAIL);
    }

    public function testQuotaClubIvsDeQuatreNouveauxContactsParSemaine(): void
    {
        for ($i = 1; $i <= 4; ++$i) {
            $prospect = (new Prospect('Membre IVS '.$i))->setSource(Source::CLUB_IVS);
            $this->journal->nouveauProspect($prospect);
            $this->journal->enregistrer($prospect, Transition::ENVOYER_MESSAGE, Canal::MAIL);
        }

        $cinquieme = (new Prospect('Membre IVS 5'))->setSource(Source::CLUB_IVS);
        $this->journal->nouveauProspect($cinquieme);

        try {
            $this->journal->enregistrer($cinquieme, Transition::ENVOYER_MESSAGE, Canal::MAIL);
            self::fail('Le 5e contact Club IVS de la semaine aurait dû être refusé.');
        } catch (SollicitationRefusee $e) {
            self::assertStringContainsString('Quota Club IVS', $e->getMessage());
        }

        // La semaine suivante, le quota repart à zéro.
        $this->clock->modify('2026-11-02 09:00');
        $this->journal->enregistrer($cinquieme, Transition::ENVOYER_MESSAGE, Canal::MAIL);
        self::assertSame(Statut::EN_ATTENTE, $cinquieme->getStatutEnum());
    }

    public function testDetecteLesFichesSansProchaineAction(): void
    {
        $avecTache = new Prospect('Avec tâche');
        $this->journal->nouveauProspect($avecTache);

        $sansTache = new Prospect('Sans tâche');
        $this->em->persist($sansTache);
        $gagne = (new Prospect('Gagné'))->setStatut(Statut::GAGNE->value);
        $this->em->persist($gagne);
        $this->em->flush();

        $noms = array_map(static fn (Prospect $p) => $p->getNom(), $this->service(ProspectRepository::class)->findSansProchaineAction());
        self::assertSame(['Sans tâche'], $noms);
    }

    /** @return list<TypeTache> */
    private function typesOuverts(Prospect $prospect): array
    {
        return array_map(static fn (Tache $t) => $t->getType(), $prospect->getTachesOuvertes());
    }

    /** @return array<string, string> */
    private function echeances(Prospect $prospect): array
    {
        $echeances = [];
        foreach ($prospect->getTachesOuvertes() as $tache) {
            $echeances[$tache->getType()->value] = $tache->getEcheance()->format('Y-m-d');
        }

        return $echeances;
    }
}
