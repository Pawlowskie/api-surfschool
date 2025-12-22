<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
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
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CANCELLED = 'cancelled';

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

    #[ORM\Column(length: 20)]
    #[Assert\Choice(['pending', 'confirmed', 'cancelled'])]
    #[Groups(['booking:read', 'booking:write', 'session:read'])]
    private string $status = self::STATUS_PENDING;

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
        $this->status ??= self::STATUS_PENDING;
    }

    #[ORM\PreRemove]
    public function onPreRemove(): void
    {
        if ($this->statusHoldsSeat($this->status) && $this->session) {
            $this->releaseSeat($this->session);
        }
    }

    public function confirm(): void
    {
        $this->setStatus(self::STATUS_CONFIRMED);
    }

    public function cancel(): void
    {
        $this->setStatus(self::STATUS_CANCELLED);
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

    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): static
    {
        $allowedStatuses = [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_CANCELLED,
        ];

        if (!\in_array($status, $allowedStatuses, true)) {
            throw new \InvalidArgumentException(sprintf('Statut "%s" invalide.', $status));
        }

        $previousStatus = $this->status ?? self::STATUS_PENDING;

        if ($status === $previousStatus) {
            return $this;
        }

        $previousHoldingSeat = $this->statusHoldsSeat($previousStatus);
        $nextHoldingSeat = $this->statusHoldsSeat($status);

        if ($nextHoldingSeat && !$previousHoldingSeat) {
            if (!$this->session) {
                if ($this->id !== null) {
                    throw new \LogicException('Impossible de réserver une place sans session associée.');
                }
            } else {
                $this->reserveSeat($this->session);
            }
        }

        if ($previousHoldingSeat && !$nextHoldingSeat && $this->session) {
            $this->releaseSeat($this->session);
        }

        $this->status = $status;

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
            throw new \LogicException('Une réservation doit toujours être rattachée à une session.');
        }

        $currentSession = $this->session;
        $holdsSeat = $this->statusHoldsSeat($this->status);

        if ($holdsSeat && $currentSession) {
            $this->releaseSeat($currentSession);
        }

        $this->session = $session;

        if ($holdsSeat) {
            $this->reserveSeat($session);
        }

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

    private function statusHoldsSeat(string $status): bool
    {
        return \in_array($status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true);
    }

    private function reserveSeat(Session $session): void
    {
        if ($session->isCancelled()) {
            throw new \LogicException('Impossible de confirmer une réservation sur une session annulée.');
        }

        $spots = $session->getAvailableSpots();
        if ($spots === null) {
            throw new \LogicException("Le nombre de places disponibles n'est pas initialisé.");
        }

        if ($spots <= 0) {
            throw new \LogicException('Aucune place disponible pour cette session.');
        }

        $session->setAvailableSpots($spots - 1);
    }

    private function releaseSeat(Session $session): void
    {
        $spots = $session->getAvailableSpots();
        if ($spots === null) {
            throw new \LogicException("Le nombre de places disponibles n'est pas initialisé.");
        }

        $session->setAvailableSpots($spots + 1);
    }
}
