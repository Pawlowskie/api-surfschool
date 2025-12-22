<?php

namespace App\DTO;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Simple DTO used when a user wants to update only his email address.
 */
final class UpdateMyEmailDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email;

    public function __construct(string $email = '')
    {
        $this->email = $email;
    }

    public function applyTo(User $user): void
    {
        $user->setEmail(mb_strtolower(trim($this->email)));
    }
}
