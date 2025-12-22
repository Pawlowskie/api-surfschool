<?php

namespace App\Command;

use App\Repository\SessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:purge-past-sessions',
    description: 'Supprime les sessions dont la date de fin est dépassée'
)]
class PurgePastSessionsCommand extends Command
{
    public function __construct(
        private readonly SessionRepository $sessionRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $reference = new \DateTimeImmutable();
        $sessions = $this->sessionRepository->findPastSessions($reference);

        if ($sessions === []) {
            $output->writeln('Aucune session expirée à supprimer.');
            return Command::SUCCESS;
        }

        foreach ($sessions as $session) {
            $output->writeln(sprintf('Suppression de la session #%d (%s).', $session->getId(), $session->getStartDate()?->format('Y-m-d H:i')));
            $this->entityManager->remove($session);
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('🗑️ %d session(s) supprimée(s).', \count($sessions)));

        return Command::SUCCESS;
    }
}
