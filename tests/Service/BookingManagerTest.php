<?php

namespace App\Tests\Service;

use App\Entity\Booking;
use App\Entity\Session;
use App\Enum\BookingStatus;
use App\Service\BookingManager;
use App\Service\SessionManager;
use PHPUnit\Framework\TestCase;

final class BookingManagerTest extends TestCase
{
    public function testStatusChangeReleasesSeat(): void
    {
        $session = $this->createMock(Session::class);
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->expects($this->once())->method('releaseSeat')->with($session);
        $sessionManager->expects($this->never())->method('reserveSeat');

        $manager = new BookingManager($sessionManager);
        $manager->syncSeats($session, $session, BookingStatus::Confirmed, BookingStatus::Cancelled);
    }

    public function testStatusChangeReservesSeat(): void
    {
        $session = $this->createMock(Session::class);
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->expects($this->once())->method('reserveSeat')->with($session);
        $sessionManager->expects($this->never())->method('releaseSeat');

        $manager = new BookingManager($sessionManager);
        $manager->syncSeats($session, $session, BookingStatus::Cancelled, BookingStatus::Confirmed);
    }

    public function testSessionChangeWithHoldingStatusMovesSeat(): void
    {
        $previousSession = $this->createMock(Session::class);
        $currentSession = $this->createMock(Session::class);
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->expects($this->once())->method('releaseSeat')->with($previousSession);
        $sessionManager->expects($this->once())->method('reserveSeat')->with($currentSession);

        $manager = new BookingManager($sessionManager);
        $manager->syncSeats($previousSession, $currentSession, BookingStatus::Confirmed, BookingStatus::Confirmed);
    }

    public function testSessionChangeWithNonHoldingStatusDoesNothing(): void
    {
        $previousSession = $this->createMock(Session::class);
        $currentSession = $this->createMock(Session::class);
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->expects($this->never())->method('releaseSeat');
        $sessionManager->expects($this->never())->method('reserveSeat');

        $manager = new BookingManager($sessionManager);
        $manager->syncSeats($previousSession, $currentSession, BookingStatus::Cancelled, BookingStatus::Cancelled);
    }

    public function testStatusAndSessionChangeReleasesOnly(): void
    {
        $previousSession = $this->createMock(Session::class);
        $currentSession = $this->createMock(Session::class);
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->expects($this->once())->method('releaseSeat')->with($previousSession);
        $sessionManager->expects($this->never())->method('reserveSeat');

        $manager = new BookingManager($sessionManager);
        $manager->syncSeats($previousSession, $currentSession, BookingStatus::Confirmed, BookingStatus::Cancelled);
    }

    public function testNoChangeDoesNothing(): void
    {
        $session = $this->createMock(Session::class);
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->expects($this->never())->method('releaseSeat');
        $sessionManager->expects($this->never())->method('reserveSeat');

        $manager = new BookingManager($sessionManager);
        $manager->syncSeats($session, $session, BookingStatus::Pending, BookingStatus::Pending);
    }

    public function testNewBookingReservesSeat(): void
    {
        $session = $this->createMock(Session::class);
        $sessionManager = $this->createMock(SessionManager::class);
        $sessionManager->expects($this->once())->method('reserveSeat')->with($session);
        $sessionManager->expects($this->never())->method('releaseSeat');

        $manager = new BookingManager($sessionManager);
        $manager->syncSeats(null, $session, BookingStatus::Cancelled, BookingStatus::Pending);
    }
}
