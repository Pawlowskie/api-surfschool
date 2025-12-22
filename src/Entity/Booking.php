<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Enum\BookingStatus;
use App\Exception\BookingSessionRequiredException;
use App\Repository\BookingRepository;
use App\State\BookingProcessor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BookingRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('ROLE_USER')"),
        new Get(security: "is_granted('ROLE_ADMIN') or object.getUser() == user"),
        new Post(
            processor: BookingProcessor::class,
            security: "is_granted('ROLE_USER')",
            securityMessage: "Vous devez être connecté pour créer une réservation."
        ),
        new Patch(security: "is_granted('ROLE_ADMIN') or object.getUser() == user"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ],
    normalizationContext: ['groups' => ['booking:read']],
    denormalizationContext: ['groups' => ['booking:write']],
)]
class Booking
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['booking:read', 'session:read', 'user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['booking:read', 'booking:write', 'session:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['booking:read', 'booking:write', 'session:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Groups(['booking:read', 'booking:write', 'session:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Groups(['booking:read', 'booking:write', 'session:read'])]
    private ?string $phone = null;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['booking:read', 'booking:write', 'session:read'])]
    private ?int $age = null;

    #[ORM\Column]
    #[Groups(['booking:read', 'booking:write', 'session:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(enumType: BookingStatus::class)]
    #[Groups(['booking:read', 'booking:write', 'session:read'])]
    private BookingStatus $status = BookingStatus::Pending;

    #[ORM\Column(type: 'string', length: 64, nullable: true, unique: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmationSentAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reminderSentAt = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['booking:read', 'booking:write'])]
    private ?Session $session = null;

    #[ORM\ManyToOne(inversedBy: 'bookings')]
    #[Groups(['booking:read'])]
    private ?User $user = null;

    // ------------------------
    // Lifecycle & Métier
    // ------------------------

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->status ??= BookingStatus::Pending;
    }

    public function confirm(): void
    {
        $this->setStatus(BookingStatus::Confirmed);
    }

    public function cancel(): void
    {
        $this->setStatus(BookingStatus::Cancelled);
    }

    // ------------------------
    // Getters / Setters
    // ------------------------

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }
    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }
    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function setPhone(string $phone): static
    {
        $this->phone = $phone;
        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }
    public function setAge(int $age): static
    {
        $this->age = $age;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getStatus(): BookingStatus
    {
        return $this->status;
    }
    public function setStatus(BookingStatus $status): static
    {
        if ($status === $this->status) {
            return $this;
        }

        $this->status = $status;

        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): static
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function getConfirmationSentAt(): ?\DateTimeImmutable
    {
        return $this->confirmationSentAt;
    }

    public function setConfirmationSentAt(?\DateTimeImmutable $confirmationSentAt): static
    {
        $this->confirmationSentAt = $confirmationSentAt;

        return $this;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?\DateTimeImmutable $confirmedAt): static
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getReminderSentAt(): ?\DateTimeImmutable
    {
        return $this->reminderSentAt;
    }

    public function setReminderSentAt(?\DateTimeImmutable $reminderSentAt): static
    {
        $this->reminderSentAt = $reminderSentAt;

        return $this;
    }

    public function getSession(): ?Session
    {
        return $this->session;
    }
    public function setSession(?Session $session): static
    {
        if ($this->session === $session) {
            return $this;
        }

        if ($session === null) {
            throw new BookingSessionRequiredException('Une réservation doit toujours être rattachée à une session.');
        }

        $this->session = $session;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

}
