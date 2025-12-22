<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\UpdateMyEmailDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use LogicException;

final class UpdateMyEmailProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
    ) {
    }

    /**
     * Receives the DTO populated by Api Platform and applies it to the logged-in user.
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): User {
        /** @var UpdateMyEmailDto $data */

        // Fetch the currently authenticated user; updating the email only makes sense for a logged-in account.
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new LogicException('No authenticated user.');
        }

        // Let the DTO update the entity (handles trimming, lowercase, validation already done upstream).
        $data->applyTo($user);

        // Persist the change immediately; no need to call persist() because the User is already managed.
        $this->em->flush();

        return $user;
    }
}
