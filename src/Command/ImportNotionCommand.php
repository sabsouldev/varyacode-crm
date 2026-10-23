<?php

namespace App\Command;

use App\Entity\Contact;
use App\Entity\Prospect;
use App\Entity\Tache;
use App\Enum\Canal;
use App\Enum\Offre;
use App\Enum\Priorite;
use App\Enum\Secteur;
use App\Enum\SiteActuel;
use App\Enum\Source;
use App\Enum\Statut;
use App\Enum\TypeTache;
use App\Import\LibelleNotion;
use App\Prospection\PlanificateurRelances;
use App\Repository\ProspectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Import unique de la base Notion « Prospects » à partir de son export CSV
 * (menu ••• de la base → Exporter → Markdown & CSV).
 */
#[AsCommand('app:import:notion', 'Importe les prospects depuis l\'export CSV de la base Notion')]
final class ImportNotionCommand
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ProspectRepository $prospects,
        private readonly PlanificateurRelances $planificateur,
        private readonly ClockInterface $clock,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument('Chemin du fichier CSV exporté depuis Notion')]
        string $fichier,
        #[Option('Affiche ce qui serait importé sans rien enregistrer')]
        bool $dryRun = false,
    ): int {
        $lignes = $this->lire($fichier);
        $importes = 0;
        $ignores = [];
        $vus = [];

        foreach ($lignes as $numero => $ligne) {
            $nom = trim($ligne['Entreprise'] ?? '');
            if ('' === $nom) {
                $ignores[] = \sprintf('ligne %d : pas de nom d\'entreprise', $numero);
                continue;
            }
            // Doublons dans le fichier lui-même, puis avec la base.
            $cle = mb_strtolower($nom);
            if (isset($vus[$cle]) || null !== $this->prospects->findOneByNomInsensible($nom)) {
                $ignores[] = \sprintf('%s : déjà présent', $nom);
                continue;
            }
            $vus[$cle] = true;

            $prospect = $this->creerProspect($nom, $ligne);
            if (!$dryRun) {
                $this->em->persist($prospect);
            }
            ++$importes;
        }

        if (!$dryRun) {
            $this->em->flush();
        }

        foreach ($ignores as $raison) {
            $io->writeln(' - ignoré, '.$raison);
        }
        $io->success(\sprintf('%d prospect(s) %s, %d ignoré(s).', $importes, $dryRun ? 'à importer' : 'importé(s)', \count($ignores)));

        return Command::SUCCESS;
    }

    /** @param array<string, string> $ligne */
    private function creerProspect(string $nom, array $ligne): Prospect
    {
        $prospect = (new Prospect($nom, $this->clock->now()))
            ->setSecteur(LibelleNotion::versEnum(Secteur::class, $ligne['Secteur'] ?? null))
            ->setSource(LibelleNotion::versEnum(Source::class, $ligne['Source'] ?? null))
            ->setSiteActuel(LibelleNotion::versEnum(SiteActuel::class, $ligne['Site actuel'] ?? null) ?? SiteActuel::A_VERIFIER)
            ->setPriorite(LibelleNotion::versEnum(Priorite::class, $ligne['Priorité'] ?? null) ?? Priorite::MOYENNE)
            ->setOffreVisee(LibelleNotion::versEnum(Offre::class, $ligne['Offre visée'] ?? null) ?? Offre::A_DEFINIR)
            ->setCanalPrefere(LibelleNotion::versEnum(Canal::class, $ligne['Canal'] ?? null))
            ->setBesoinIdentifie($this->booleen($ligne['Besoin identifié'] ?? ''))
            ->setCapaciteBudget($this->booleen($ligne['Capacité / budget'] ?? ''))
            ->setIntentionAffirmee($this->booleen($ligne['Intention affirmée'] ?? ''))
            ->setNbRelances((int) ($ligne['Nb relances'] ?? 0))
            ->setDernierContact($this->date($ligne['Dernier contact'] ?? ''))
            ->setNotes($this->texte($ligne['Notes'] ?? ''));

        // Reprise de l'état existant : on positionne le statut sans passer par une transition.
        $statut = LibelleNotion::versEnum(Statut::class, $ligne['Statut'] ?? null) ?? Statut::A_CONTACTER;
        $prospect->setStatut($statut->value);

        $contact = $this->texte($ligne['Contact'] ?? '');
        $email = $this->texte($ligne['Email'] ?? '');
        $telephone = $this->texte($ligne['Téléphone'] ?? '');
        if (null !== $contact || null !== $email || null !== $telephone) {
            (new Contact($prospect))->setNom($contact)->setEmail($email)->setTelephone($telephone);
        }

        if (!$statut->estClos()) {
            $this->reprendreProchaineAction($prospect, $statut, $ligne);
        }

        return $prospect;
    }

    /** @param array<string, string> $ligne */
    private function reprendreProchaineAction(Prospect $prospect, Statut $statut, array $ligne): void
    {
        $echeance = $this->date($ligne['Prochaine action'] ?? '');
        $action = $this->texte($ligne['Action à faire'] ?? '');

        if (null === $echeance && null === $action && Statut::A_CONTACTER === $statut) {
            $this->planificateur->initialiser($prospect);

            return;
        }

        $type = match ($statut) {
            Statut::A_CONTACTER => TypeTache::VERIFIER,
            Statut::EN_ATTENTE => TypeTache::RELANCER,
            Statut::DEVIS_ENVOYE => TypeTache::RELANCER_DEVIS,
            Statut::PAS_MAINTENANT => TypeTache::REPRENDRE_CONTACT,
            default => TypeTache::AUTRE,
        };
        new Tache($prospect, $type, $echeance ?? $this->clock->now(), $action ?? 'Définir la prochaine action');
    }

    /** @return array<int, array<string, string>> lignes indexées par numéro de ligne du fichier */
    private function lire(string $fichier): array
    {
        $flux = @fopen($fichier, 'r');
        if (false === $flux) {
            throw new \InvalidArgumentException(\sprintf('Fichier illisible : %s', $fichier));
        }

        $entetes = fgetcsv($flux, escape: '');
        if (false === $entetes) {
            return [];
        }
        // Notion préfixe le fichier d'un BOM UTF-8.
        $entetes = array_map(static fn (?string $e) => trim(str_replace("\u{FEFF}", '', (string) $e)), $entetes);

        $lignes = [];
        $numero = 1;
        while (false !== ($valeurs = fgetcsv($flux, escape: ''))) {
            ++$numero;
            if ([null] === $valeurs) {
                continue;
            }
            $lignes[$numero] = array_combine($entetes, array_pad(array_slice($valeurs, 0, \count($entetes)), \count($entetes), ''));
        }
        fclose($flux);

        return $lignes;
    }

    private function booleen(string $valeur): bool
    {
        return \in_array(mb_strtolower(trim($valeur)), ['yes', 'oui', 'true', '1', '__yes__'], true);
    }

    private function texte(string $valeur): ?string
    {
        $valeur = trim($valeur);

        return '' === $valeur ? null : $valeur;
    }

    /** Notion exporte selon la langue du compte : « October 20, 2026 », « 20/10/2026 » ou « 2026-10-20 ». */
    private function date(string $valeur): ?\DateTimeImmutable
    {
        $valeur = trim(explode('→', $valeur)[0]);
        if ('' === $valeur) {
            return null;
        }

        foreach (['!Y-m-d', '!d/m/Y', '!F j, Y', '!M j, Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $valeur);
            if (false !== $date) {
                return $date;
            }
        }

        $mois = ['janvier' => 1, 'février' => 2, 'fevrier' => 2, 'mars' => 3, 'avril' => 4, 'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8, 'aout' => 8, 'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12, 'decembre' => 12];
        if (preg_match('/^(\d{1,2})\s+(\p{L}+)\s+(\d{4})/u', mb_strtolower($valeur), $m) && isset($mois[$m[2]])) {
            return new \DateTimeImmutable(\sprintf('%d-%02d-%02d', $m[3], $mois[$m[2]], $m[1]));
        }

        return null;
    }
}
