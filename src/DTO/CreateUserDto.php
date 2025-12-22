<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Public registration payload.
 */
final class CreateUserDto
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password;

    public function __construct(string $email = '', string $password = '')
    {
        $this->email = $email;
        $this->password = $password;
    }
}
