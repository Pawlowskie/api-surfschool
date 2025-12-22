<?php

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Admin-only DTO to update user roles.
 */
final class UpdateUserRolesDto
{
    /**
     * @var list<string>
     */
    #[Assert\NotBlank]
    #[Assert\All([
        new Assert\Choice(choices: ['ROLE_USER', 'ROLE_ADMIN', 'ROLE_INSTRUCTOR']),
    ])]
    public array $roles;

    /**
     * @param list<string> $roles
     */
    public function __construct(array $roles = ['ROLE_USER'])
    {
        $this->roles = $roles ?: ['ROLE_USER'];
    }
}
