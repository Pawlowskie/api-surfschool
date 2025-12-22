<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\Session;
use App\Enum\BookingStatus;

final class BookingManager
{
    public function __construct(private readonly SessionManager $sessionManager)
    {
    }

    public function statusHoldsSeat(?BookingStatus $status): bool
    {
        $status ??= BookingStatus::Pending;

        return $status->holdsSeat();
    }

    public function syncSeats(
        ?Session $previousSession,
        ?Session $currentSession,
        ?BookingStatus $previousStatus,
        ?BookingStatus $currentStatus
    ): void {
        $previousHolds = $this->statusHoldsSeat($previousStatus);
        $currentHolds = $this->statusHoldsSeat($currentStatus);

        if ($previousSession !== $currentSession) {
            if ($previousHolds && $previousSession) {
                $this->sessionManager->releaseSeat($previousSession);
            }

            if ($currentHolds && $currentSession) {
                $this->sessionManager->reserveSeat($currentSession);
            }

            return;
        }

        if ($previousHolds && !$currentHolds && $currentSession) {
            $this->sessionManager->releaseSeat($currentSession);
        }

        if (!$previousHolds && $currentHolds && $currentSession) {
            $this->sessionManager->reserveSeat($currentSession);
        }
    }
}
