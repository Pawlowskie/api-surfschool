<?php

namespace App\Command;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:cancel-expired-bookings',
    description: 'Annule automatiquement les réservations en attente depuis plus de 12h'
)]
class CancelExpiredBookingsCommand extends Command
{
    private const WINDOW = '+12 hours';

    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $deadline = new \DateTimeImmutable(self::WINDOW);
        $bookings = $this->bookingRepository->findPendingStartingBefore($deadline);

        if ($bookings === []) {
            $output->writeln('Aucune réservation expirée trouvée.');
            return Command::SUCCESS;
        }

        foreach ($bookings as $booking) {
            if ($booking->getStatus() === Booking::STATUS_PENDING) {
                $booking->cancel();
            }
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('✅ %d réservation(s) annulée(s).', \count($bookings)));

        return Command::SUCCESS;
    }
}
