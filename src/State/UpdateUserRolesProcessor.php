<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\UpdateUserRolesDto;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Allows admins to update the role list of a user.
 */
final class UpdateUserRolesProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        /** @var UpdateUserRolesDto $data */
        $userId = $uriVariables['id'] ?? null;
        $user = $userId ? $this->userRepository->find($userId) : null;

        if (!$user instanceof User) {
            throw new NotFoundHttpException('User not found.');
        }

        // Enforce at least ROLE_USER
        $roles = array_unique(array_merge($data->roles, ['ROLE_USER']));
        $user->setRoles($roles);

        $this->entityManager->flush();

        return $user;
    }
}
