<?php

namespace App\EventSubscriber;

use App\Entity\Booking;
use App\Entity\Session;
use App\Enum\BookingStatus;
use App\Exception\BookingSessionRequiredException;
use App\Service\BookingManager;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

final class BookingSeatSubscriber implements EventSubscriber
{
    public function __construct(private readonly BookingManager $bookingManager)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
            Events::preRemove,
        ];
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $booking = $args->getObject();
        if (!$booking instanceof Booking) {
            return;
        }

        $session = $booking->getSession();
        if (!$session) {
            throw new BookingSessionRequiredException('Une réservation active doit être rattachée à une session.');
        }

        $this->bookingManager->syncSeats(null, $session, BookingStatus::Cancelled, $booking->getStatus());
        $this->recomputeSessionChangeSet($args, $session);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $booking = $args->getObject();
        if (!$booking instanceof Booking) {
            return;
        }

        $statusChanged = $args->hasChangedField('status');
        $sessionChanged = $args->hasChangedField('session');

        if (!$statusChanged && !$sessionChanged) {
            return;
        }

        $previousStatus = $statusChanged ? $args->getOldValue('status') : $booking->getStatus();
        $currentStatus = $booking->getStatus();

        if (\is_string($previousStatus)) {
            $previousStatus = BookingStatus::from($previousStatus);
        }
        if (\is_string($currentStatus)) {
            $currentStatus = BookingStatus::from($currentStatus);
        }

        $previousSession = $sessionChanged ? $args->getOldValue('session') : $booking->getSession();
        $currentSession = $booking->getSession();

        $this->bookingManager->syncSeats($previousSession, $currentSession, $previousStatus, $currentStatus);

        $this->recomputeSessionChangeSet($args, $previousSession);
        if ($currentSession !== $previousSession) {
            $this->recomputeSessionChangeSet($args, $currentSession);
        }
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $booking = $args->getObject();
        if (!$booking instanceof Booking) {
            return;
        }

        if (!$this->bookingManager->statusHoldsSeat($booking->getStatus())) {
            return;
        }

        $session = $booking->getSession();
        if (!$session) {
            return;
        }

        $this->bookingManager->syncSeats($session, null, $booking->getStatus(), BookingStatus::Cancelled);
        $this->recomputeSessionChangeSet($args, $session);
    }

    private function recomputeSessionChangeSet(LifecycleEventArgs $args, ?Session $session): void
    {
        if (!$session) {
            return;
        }

        $objectManager = $args->getObjectManager();
        if (!$objectManager instanceof EntityManagerInterface) {
            return;
        }

        $unitOfWork = $objectManager->getUnitOfWork();
        $unitOfWork->computeChangeSet(
            $objectManager->getClassMetadata(Session::class),
            $session
        );
    }
}
