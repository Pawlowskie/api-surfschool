<?php

namespace App\Tests\Integration;

use App\Entity\Booking;
use App\Entity\Course;
use App\Entity\Session;
use App\Entity\User;
use App\Enum\BookingStatus;
use App\Tests\Support\WebTestCaseBase;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class BookingPermissionsTest extends WebTestCaseBase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private JWTTokenManagerInterface $jwtManager;
    private $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $this->jwtManager = self::getContainer()->get(JWTTokenManagerInterface::class);

        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function testBookingsCollectionRequiresAuth(): void
    {
        $this->client->request('GET', '/api/bookings');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testUserCanAccessOwnBookingButCannotDelete(): void
    {
        $fixtures = $this->seedBookingFixtures();
        $booking = $fixtures['booking'];
        $user = $fixtures['owner'];
        $admin = $fixtures['admin'];

        $userToken = $this->jwtManager->create($user);

        $this->client->request(
            'GET',
            '/api/bookings/'.$booking->getId(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$userToken]
        );
        $this->assertResponseStatusCodeSame(200);

        $this->client->request(
            'DELETE',
            '/api/bookings/'.$booking->getId(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$userToken]
        );
        $this->assertResponseStatusCodeSame(403);

        $adminToken = $this->jwtManager->create($admin);
        $this->client->request(
            'DELETE',
            '/api/bookings/'.$booking->getId(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$adminToken]
        );
        $this->assertResponseStatusCodeSame(204);
    }

    public function testUserCannotAccessOthersBooking(): void
    {
        $fixtures = $this->seedBookingFixtures();
        $booking = $fixtures['booking'];
        $other = $fixtures['other'];

        $otherToken = $this->jwtManager->create($other);
        $this->client->request(
            'GET',
            '/api/bookings/'.$booking->getId(),
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer '.$otherToken]
        );
        $this->assertResponseStatusCodeSame(403);
    }

    /**
     * @return array{booking: Booking, owner: User, admin: User, other: User}
     */
    private function seedBookingFixtures(): array
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
            ->setCapacity(5);

        $user = $this->createUser('user@example.com', ['ROLE_USER']);
        $other = $this->createUser('other@example.com', ['ROLE_USER']);
        $admin = $this->createUser('admin@example.com', ['ROLE_ADMIN']);

        $booking = (new Booking())
            ->setFirstName('Test')
            ->setLastName('User')
            ->setEmail('user@example.com')
            ->setPhone('0600000000')
            ->setAge(20)
            ->setStatus(BookingStatus::Pending)
            ->setSession($session)
            ->setUser($user);

        $this->entityManager->persist($course);
        $this->entityManager->persist($session);
        $this->entityManager->persist($user);
        $this->entityManager->persist($other);
        $this->entityManager->persist($admin);
        $this->entityManager->persist($booking);
        $this->entityManager->flush();

        return [
            'booking' => $booking,
            'owner' => $user,
            'admin' => $admin,
            'other' => $other,
        ];
    }

    private function createUser(string $email, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setIsVerified(true);

        $hashed = $this->passwordHasher->hashPassword($user, 'password');
        $user->setPassword($hashed);

        return $user;
    }

    // Auth handled via JWT tokens because API firewall is stateless.
}
