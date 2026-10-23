<?php

namespace App\Command;

use App\Enum\Statut;
use App\Enum\TypeTache;
use App\Prospection\JournalProspection;
use App\Prospection\Transition;
use App\Repository\TacheRepository;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Scheduler\Attribute\AsCronTask;

/**
 * Applique la règle « 2 relances sans réponse → Pas maintenant, reprise dans 3 mois ».
 * Tourne chaque jour ouvré à 7 h via le Scheduler, ou directement par un cron.
 */
#[AsCommand('app:prospection:cloturer-sans-reponse', 'Met en pause les prospects restés sans réponse après toutes les relances')]
#[AsCronTask('0 7 * * 1-5', timezone: 'Europe/Paris')]
final class CloturerSansReponseCommand extends Command
{
    public function __construct(
        private readonly TacheRepository $taches,
        private readonly JournalProspection $journal,
        private readonly ClockInterface $clock,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $nb = 0;

        foreach ($this->taches->findOuvertesJusquAu($this->clock->now(), TypeTache::CLOTURER_SANS_REPONSE) as $tache) {
            $prospect = $tache->getProspect();
            if (Statut::EN_ATTENTE !== $prospect->getStatutEnum()) {
                $tache->annuler($this->clock->now());
                continue;
            }

            $this->journal->enregistrer($prospect, Transition::METTRE_EN_PAUSE, null, \sprintf('Sans réponse après %d relances', $prospect->getNbRelances()));
            $io->writeln(\sprintf('Mis en pause : %s', $prospect->getNom()));
            ++$nb;
        }

        $io->success(\sprintf('%d prospect(s) mis en pause.', $nb));

        return Command::SUCCESS;
    }
}
