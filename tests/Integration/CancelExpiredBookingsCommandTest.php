<?php

namespace App\Tests\Integration;

use App\Entity\Booking;
use App\Entity\Course;
use App\Entity\Session;
use App\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use App\Tests\Support\KernelTestCaseBase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;

final class CancelExpiredBookingsCommandTest extends KernelTestCaseBase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::getContainer()->get(MessageLoggerListener::class)->reset();

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testPendingBookingIsCancelledAndEmailSent(): void
    {
        $logger = self::getContainer()->get(MessageLoggerListener::class);
        $logger->reset();

        $course = (new Course())
            ->setTitle('Cours test')
            ->setLevel('Débutant')
            ->setTargetAudience('both')
            ->setDuration(120)
            ->setBasePrice(30);

        $session = (new Session())
            ->setCourse($course)
            ->setStartDate(new \DateTimeImmutable('+6 hours'))
            ->setCapacity(2);

        $this->entityManager->persist($course);
        $this->entityManager->persist($session);
        $this->entityManager->flush();

        $booking = (new Booking())
            ->setFirstName('Test')
            ->setLastName('User')
            ->setEmail('test@example.com')
            ->setPhone('0600000000')
            ->setAge(20)
            ->setStatus(BookingStatus::Pending)
            ->setSession($session);

        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        $application = new Application(self::$kernel);
        $command = $application->find('app:cancel-expired-bookings');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->entityManager->refresh($booking);
        $this->assertSame(BookingStatus::Cancelled, $booking->getStatus());

        $events = $logger->getEvents()->getEvents();
        $matching = array_values(array_filter(
            $events,
            static fn ($event) => $event->getMessage()->getSubject() === 'Réservation annulée (non confirmée)'
        ));

        $this->assertGreaterThanOrEqual(1, \count($matching));
        $message = $matching[0]->getMessage();
        $this->assertSame('Réservation annulée (non confirmée)', $message->getSubject());
    }
}
