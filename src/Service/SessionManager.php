<?php

namespace App\Service;

use App\Entity\Session;
use App\Exception\AvailableSpotsNotInitializedException;
use App\Exception\CapacityExceededException;
use App\Exception\NoAvailableSpotsException;
use App\Exception\SessionCancelledException;

class SessionManager
{
    public function reserveSeat(Session $session): void
    {
        if ($session->isCancelled()) {
            throw new SessionCancelledException('Impossible de confirmer une réservation sur une session annulée.');
        }

        $spots = $session->getAvailableSpots();
        if ($spots === null) {
            throw new AvailableSpotsNotInitializedException("Le nombre de places disponibles n'est pas initialisé.");
        }

        if ($spots <= 0) {
            throw new NoAvailableSpotsException('Aucune place disponible pour cette session.');
        }

        $session->setAvailableSpots($spots - 1);
    }

    public function releaseSeat(Session $session): void
    {
        $spots = $session->getAvailableSpots();
        if ($spots === null) {
            throw new AvailableSpotsNotInitializedException("Le nombre de places disponibles n'est pas initialisé.");
        }

        $capacity = $session->getCapacity();
        if ($capacity !== null && $spots >= $capacity) {
            throw new CapacityExceededException('Impossible de libérer plus de places que la capacité.');
        }

        $session->setAvailableSpots($spots + 1);
    }
}
