<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO for updating the current user's password.
 */
final class ChangePasswordDto
{
    #[Assert\NotBlank]
    public string $currentPassword;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8, max: 4096)]
    public string $password;

    public function __construct(string $currentPassword = '', string $password = '')
    {
        $this->currentPassword = $currentPassword;
        $this->password = $password;
    }
}
