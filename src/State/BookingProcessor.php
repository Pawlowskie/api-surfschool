<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Booking;
use App\Enum\BookingStatus;
use App\Message\SendBookingConfirmationEmailMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

final class BookingProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private readonly ProcessorInterface $persistProcessor,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Booking && null === $data->getUser()) {
            $user = $this->security->getUser();
            if (\is_object($user)) {
                $data->setUser($user);
            }
        }

        return $this->entityManager->wrapInTransaction(function () use ($data, $operation, $uriVariables, $context) {
            if (!$data instanceof Booking) {
                return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
            }

            $isAdmin = $this->security->isGranted('ROLE_ADMIN');
            if (!$isAdmin) {
                $data->setStatus(BookingStatus::Pending);
            }

            if ($data->getStatus() === BookingStatus::Pending) {
                $data->setConfirmationToken(bin2hex(random_bytes(32)));
                $data->setConfirmationSentAt(new \DateTimeImmutable());
                $data->setConfirmedAt(null);
            } else {
                $data->setConfirmationToken(null);
                $data->setConfirmationSentAt(null);
                if ($data->getStatus() === BookingStatus::Confirmed) {
                    $data->setConfirmedAt(new \DateTimeImmutable());
                }
            }

            $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

            if ($data->getStatus() === BookingStatus::Pending && $data->getConfirmationToken()) {
                $session = $data->getSession();
                $courseTitle = $session?->getCourse()?->getTitle() ?? 'Session de surf';
                $sessionStart = $session?->getStartDate()?->format(DATE_ATOM) ?? '';

                $this->messageBus->dispatch(new SendBookingConfirmationEmailMessage(
                    $data->getEmail(),
                    $data->getConfirmationToken(),
                    $courseTitle,
                    $sessionStart
                ));
            }

            return $result;
        });
    }
}
