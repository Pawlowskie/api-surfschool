<?php

namespace App\Controller;

use App\Enum\BookingStatus;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ConfirmBookingController
{
    public function __construct(
        private readonly BookingRepository $bookingRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/api/bookings/confirm/{token}', name: 'confirm_booking', methods: ['GET'])]
    public function __invoke(string $token): Response
    {
        $booking = $this->bookingRepository->findOneBy(['confirmationToken' => $token]);

        if (!$booking) {
            return new JsonResponse(['message' => 'Token invalide.'], Response::HTTP_NOT_FOUND);
        }

        if ($booking->getStatus() === BookingStatus::Cancelled) {
            return new JsonResponse(['message' => 'Réservation annulée.'], Response::HTTP_BAD_REQUEST);
        }

        if ($booking->getStatus() === BookingStatus::Confirmed) {
            return new JsonResponse(['message' => 'Réservation déjà confirmée.'], Response::HTTP_OK);
        }

        $booking->setStatus(BookingStatus::Confirmed);
        $booking->setConfirmedAt(new \DateTimeImmutable());
        $booking->setConfirmationToken(null);
        $booking->setConfirmationSentAt(null);

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Réservation confirmée.'], Response::HTTP_OK);
    }
}
