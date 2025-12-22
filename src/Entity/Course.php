<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CourseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CourseRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['course:read']],
    denormalizationContext: ['groups' => ['course:write']],
    operations: [
        new GetCollection(security: "is_granted('PUBLIC_ACCESS')"),
        new Get(security: "is_granted('PUBLIC_ACCESS')"),
        new Post(security: "is_granted('ROLE_ADMIN')"),
        new Patch(security: "is_granted('ROLE_ADMIN')"),
        new Delete(security: "is_granted('ROLE_ADMIN')")
    ]
)]
class Course
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['course:read', 'session:read', 'booking:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write', 'session:read'])]
    private ?string $title = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    #[Groups(['course:read', 'course:write', 'session:read'])]
    private ?string $level = null;

    #[ORM\Column]
    #[Groups(['course:read', 'course:write', 'session:read'])]
    private ?bool $isPrivate = false;

    #[ORM\Column(length: 50)]
    #[Assert\Choice(['adult', 'child', 'both'])]
    #[Groups(['course:read', 'course:write', 'session:read'])]
    private ?string $targetAudience = 'both';

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['course:read', 'course:write', 'session:read'])]
    private ?int $duration = null;

    #[ORM\Column]
    #[Assert\Positive]
    #[Groups(['course:read', 'course:write', 'session:read'])]
    private ?int $basePrice = null;

    /**
     * @var Collection<int, Session>
     */
    #[ORM\OneToMany(targetEntity: Session::class, mappedBy: 'course')]
    #[Groups(['course:read', 'course:write'])]
    private Collection $sessions;

    public function __construct()
    {
        $this->sessions = new ArrayCollection();
    }

    // --------------------
    // Getters / Setters
    // --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function isPrivate(): ?bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): static
    {
        $this->isPrivate = $isPrivate;
        return $this;
    }

    public function getTargetAudience(): ?string
    {
        return $this->targetAudience;
    }

    public function setTargetAudience(string $targetAudience): static
    {
        $this->targetAudience = $targetAudience;
        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): static
    {
        $this->duration = $duration;
        return $this;
    }

    public function getBasePrice(): ?int
    {
        return $this->basePrice;
    }

    public function setBasePrice(int $basePrice): static
    {
        $this->basePrice = $basePrice;
        return $this;
    }

    /**
     * @return Collection<int, Session>
     */
    public function getSessions(): Collection
    {
        return $this->sessions;
    }

    public function addSession(Session $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setCourse($this);
        }

        return $this;
    }

    public function removeSession(Session $session): static
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getCourse() === $this) {
                $session->setCourse(null);
            }
        }

        return $this;
    }
}
