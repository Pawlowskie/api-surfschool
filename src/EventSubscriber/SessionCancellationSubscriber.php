<?php

namespace App\EventSubscriber;

use App\Entity\Booking;
use App\Entity\Session;
use App\Enum\BookingStatus;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;

final class SessionCancellationSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [Events::preUpdate];
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $session = $args->getObject();
        if (!$session instanceof Session) {
            return;
        }

        if (!$args->hasChangedField('isCancelled')) {
            return;
        }

        $newValue = $args->getNewValue('isCancelled');
        if ($newValue !== true) {
            return;
        }

        $objectManager = $args->getObjectManager();
        if (!$objectManager instanceof EntityManagerInterface) {
            return;
        }

        $unitOfWork = $objectManager->getUnitOfWork();
        $bookingMetadata = $objectManager->getClassMetadata(Booking::class);

        foreach ($session->getBookings() as $booking) {
            if ($booking->getStatus() === BookingStatus::Cancelled) {
                continue;
            }

            $booking->cancel();
            $unitOfWork->recomputeSingleEntityChangeSet($bookingMetadata, $booking);
        }
    }
}
