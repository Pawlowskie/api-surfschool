<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Returns the currently authenticated user for the /me endpoint.
 */
/**
 * Api Platform provider that returns the current authenticated user.
 */
/**
 * Api Platform provider that returns the current authenticated user for the /me endpoint.
 */
final class MeProvider implements ProviderInterface
{
    public function __construct(private Security $security)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): User
    {
        // Api Platform already checks security expressions, but double-check for safety.
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('No authenticated user.');
        }

        return $user;
    }
}
