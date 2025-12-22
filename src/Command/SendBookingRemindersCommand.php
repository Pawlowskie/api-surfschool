<?php

namespace App\Command;

use App\Enum\BookingStatus;
use App\Message\SendBookingReminderEmailMessage;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:send-booking-reminders',
    description: 'Envoie les rappels pour les sessions prévues dans 24h'
)]
final class SendBookingRemindersCommand extends Command
{
    private const WINDOW_MINUTES = 30;

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
        $from = $now->modify('+24 hours');
        $to = $from->modify(sprintf('+%d minutes', self::WINDOW_MINUTES));

        $bookings = $this->bookingRepository->findStartingBetweenWithStatuses(
            $from,
            $to,
            [BookingStatus::Pending, BookingStatus::Confirmed]
        );

        if ($bookings === []) {
            $output->writeln('Aucune réservation à rappeler.');
            return Command::SUCCESS;
        }

        foreach ($bookings as $booking) {
            $session = $booking->getSession();
            $courseTitle = $session?->getCourse()?->getTitle() ?? 'Session de surf';
            $sessionStart = $session?->getStartDate()?->format('Y-m-d H:i') ?? '';

            $token = null;
            if ($booking->getStatus() === BookingStatus::Pending) {
                $token = $booking->getConfirmationToken();
                if (!$token) {
                    $token = bin2hex(random_bytes(32));
                    $booking->setConfirmationToken($token);
                    $booking->setConfirmationSentAt(new \DateTimeImmutable());
                }
            }

            $this->messageBus->dispatch(new SendBookingReminderEmailMessage(
                $booking->getEmail(),
                $booking->getStatus(),
                $courseTitle,
                $sessionStart,
                $token
            ));

            $booking->setReminderSentAt($now);
        }

        $this->entityManager->flush();
        $output->writeln(sprintf('✅ %d rappel(s) envoyé(s).', \count($bookings)));

        return Command::SUCCESS;
    }
}
