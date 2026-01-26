<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\ChangePasswordDto;
use App\Entity\User;
use LogicException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class ChangePasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly UserPasswordHasherProcessor $passwordHasherProcessor,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    /**
     * Applies the new password to the currently authenticated user.
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): User {
        /** @var ChangePasswordDto $data */

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new LogicException('No authenticated user.');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $data->currentPassword)) {
            throw new BadRequestHttpException('Current password is invalid.');
        }

        $user->setPlainPassword($data->password);

        return $this->passwordHasherProcessor->process($user, $operation, $uriVariables, $context);
    }
}
