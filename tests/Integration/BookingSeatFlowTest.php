<?php

namespace App\Tests\Integration;

use App\Entity\Booking;
use App\Entity\Course;
use App\Entity\Session;
use App\Enum\BookingStatus;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use App\Tests\Support\KernelTestCaseBase;

final class BookingSeatFlowTest extends KernelTestCaseBase
{
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testBookingCreationReservesSeat(): void
    {
        $course = (new Course())
            ->setTitle('Cours test')
            ->setLevel('Débutant')
            ->setTargetAudience('both')
            ->setDuration(120)
            ->setBasePrice(30);

        $session = (new Session())
            ->setCourse($course)
            ->setStartDate(new \DateTimeImmutable('+2 days'))
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
        $this->entityManager->clear();

        $reloadedSession = $this->entityManager->getRepository(Session::class)->find($session->getId());
        $this->assertNotNull($reloadedSession);
        $this->assertSame(1, $reloadedSession->getAvailableSpots());
    }
}
