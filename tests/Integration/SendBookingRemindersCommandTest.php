<?php

namespace App\Tests\Integration;

use App\Entity\Booking;
use App\Entity\Course;
use App\Entity\Session;
use App\Enum\BookingStatus;
use App\Tests\Support\KernelTestCaseBase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\EventListener\MessageLoggerListener;

final class SendBookingRemindersCommandTest extends KernelTestCaseBase
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

    public function testRemindersAreSentForPendingAndConfirmed(): void
    {
        $course = (new Course())
            ->setTitle('Cours test')
            ->setLevel('Débutant')
            ->setTargetAudience('both')
            ->setDuration(120)
            ->setBasePrice(30);

        $this->entityManager->persist($course);
        $this->entityManager->flush();

        $session = new Session();
        $session->setStartDate((new \DateTimeImmutable())->modify('+24 hours +10 minutes'));
        $session->setCapacity(5);
        $session->setCourse($course);
        $course->addSession($session);

        $pending = (new Booking())
            ->setFirstName('Pending')
            ->setLastName('User')
            ->setEmail('pending@example.com')
            ->setPhone('0600000001')
            ->setAge(20)
            ->setStatus(BookingStatus::Pending)
            ->setSession($session);

        $confirmed = (new Booking())
            ->setFirstName('Confirmed')
            ->setLastName('User')
            ->setEmail('confirmed@example.com')
            ->setPhone('0600000002')
            ->setAge(21)
            ->setStatus(BookingStatus::Confirmed)
            ->setConfirmedAt(new \DateTimeImmutable())
            ->setSession($session);

        $this->entityManager->persist($session);
        $this->entityManager->flush();

        $this->entityManager->persist($pending);
        $this->entityManager->persist($confirmed);
        $this->entityManager->flush();

        $application = new Application(self::$kernel);
        $command = $application->find('app:send-booking-reminders');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->entityManager->refresh($pending);
        $this->entityManager->refresh($confirmed);

        $this->assertNotNull($pending->getReminderSentAt());
        $this->assertNotNull($confirmed->getReminderSentAt());
        $this->assertNotNull($pending->getConfirmationToken());

        $logger = self::getContainer()->get(MessageLoggerListener::class);
        $events = $logger->getEvents()->getEvents();

        $pendingMails = array_filter(
            $events,
            static fn ($event) => $event->getMessage()->getSubject() === 'Confirmez votre réservation avant la session'
        );
        $confirmedMails = array_filter(
            $events,
            static fn ($event) => $event->getMessage()->getSubject() === 'Rappel : votre session de surf approche'
        );

        $this->assertGreaterThanOrEqual(1, \count($pendingMails));
        $this->assertGreaterThanOrEqual(1, \count($confirmedMails));
    }
}
