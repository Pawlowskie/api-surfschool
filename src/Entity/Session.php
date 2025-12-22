<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Filter\SessionPastFilter;
use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Booking;


#[ORM\Entity(repositoryClass: SessionRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiResource(
    normalizationContext: ['groups' => ['session:read']],
    denormalizationContext: ['groups' => ['session:write']],
    operations: [
        new GetCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Get(security: "is_granted('PUBLIC_ACCESS')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ]
)]
#[ApiFilter(SessionPastFilter::class)]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['session:read', 'course:read', 'booking:read'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Groups(['session:read', 'session:write', 'course:read', 'booking:read'])]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(nullable: true)]
    #[Assert\GreaterThan(propertyPath: 'startDate')]
    #[Groups(['session:read', 'session:write', 'course:read', 'booking:read'])]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['session:read', 'session:write', 'course:read'])]
    private ?int $capacity = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Groups(['session:read', 'session:write', 'course:read'])]
    private ?int $availableSpots = null;

    #[ORM\ManyToOne(inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['session:read', 'session:write'])]
    private ?Course $course = null;

    #[ORM\Column(options: ['default' => false])]
    #[Groups(['session:read', 'session:write'])]
    private bool $isCancelled = false;

    /**
     * @var Collection<int, Booking>
     */
    #[ORM\OneToMany(targetEntity: Booking::class, mappedBy: 'session', cascade: ['persist', 'remove'])]
    #[Groups(['session:read:admin'])]
    private Collection $bookings;

    public function __construct()
    {
        $this->bookings = new ArrayCollection();
    }

    // --------------------------
    // Getters & Setters
    // --------------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): static
    {
        if ($capacity <= 0) {
            throw new \InvalidArgumentException('La capacité doit être positive.');
        }

        $bookedSeats = 0;

        if (null !== $this->capacity && null !== $this->availableSpots) {
            $bookedSeats = $this->capacity - $this->availableSpots;
        }

        if ($capacity < $bookedSeats) {
            throw new \LogicException('Impossible de réduire la capacité en dessous du nombre de places déjà réservées.');
        }

        $this->capacity = $capacity;

        if (null === $this->availableSpots) {
            $this->availableSpots = $capacity;
        } else {
            $this->availableSpots = $capacity - $bookedSeats;
        }

        return $this;
    }

    public function getAvailableSpots(): ?int
    {
        return $this->availableSpots;
    }

    public function setAvailableSpots(int $availableSpots): static
    {
        $this->availableSpots = $availableSpots;
        return $this;
    }

    public function getCourse(): ?Course
    {
        return $this->course;
    }

    public function setCourse(?Course $course): static
    {
        $this->course = $course;
        return $this;
    }

    public function isCancelled(): bool
    {
        return $this->isCancelled;
    }

    public function setIsCancelled(bool $isCancelled): static
    {
        $this->isCancelled = $isCancelled;

        // Si la session est annulée, on marque aussi toutes ses réservations comme annulées
        if ($isCancelled) {
            foreach ($this->bookings as $booking) {
                if (!$booking->isCancelled()) {
                    $booking->cancel();
                }
            }
        }

        return $this;
    }

    // --------------------------
    // Lifecycle Callbacks
    // --------------------------

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateEndDate(): void
    {
        if ($this->getCourse() && $this->getStartDate()) {
            // 🕒 Durée du cours exprimée en minutes
            $duration = $this->getCourse()->getDuration();
            $this->endDate = $this->startDate->modify(sprintf('+%d minutes', $duration));
        }
    }

    #[ORM\PrePersist]
    public function initializeAvailableSpots(): void
    {
        if ($this->availableSpots === null) {
            $this->availableSpots = $this->capacity;
        }
    }

    /**
     * @return Collection<int, Booking>
     */
    public function getBookings(): Collection
    {
        return $this->bookings;
    }

    public function addBooking(Booking $booking): static
    {
        if (!$this->bookings->contains($booking)) {
            $this->bookings->add($booking);
            $booking->setSession($this);
        }

        return $this;
    }

    public function removeBooking(Booking $booking): static
    {
        if ($this->bookings->removeElement($booking)) {
            if ($booking->getSession() === $this) {
                $booking->setSession(null);
            }
        }

        return $this;
    }

    #[Groups(['session:read'])]
    public function isPast(): bool
    {
        $referenceDate = $this->endDate ?? $this->startDate;

        if (!$referenceDate) {
            return false;
        }

        $now = new \DateTimeImmutable();

        return $referenceDate < $now;
    }
}
