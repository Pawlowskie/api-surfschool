<?php

namespace App\Command;

use App\Enum\BookingStatus;
use App\Message\SendBookingCancellationEmailMessage;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

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
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTimeImmutable();
        $deadline = $now->modify(self::WINDOW);
        $bookings = $this->bookingRepository->findPendingStartingBetween($now, $deadline);

        if ($bookings === []) {
            $output->writeln('Aucune réservation expirée trouvée.');
            return Command::SUCCESS;
        }

        foreach ($bookings as $booking) {
            if ($booking->getStatus() !== BookingStatus::Pending) {
                continue;
            }

            $booking->cancel();

            $session = $booking->getSession();
            $courseTitle = $session?->getCourse()?->getTitle() ?? 'Session de surf';
            $sessionStart = $session?->getStartDate()?->format('Y-m-d H:i') ?? '';

            $this->messageBus->dispatch(new SendBookingCancellationEmailMessage(
                $booking->getEmail(),
                $courseTitle,
                $sessionStart
            ));
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('✅ %d réservation(s) annulée(s).', \count($bookings)));

        return Command::SUCCESS;
    }
}
