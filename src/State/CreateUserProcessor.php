<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\DTO\CreateUserDto;
use App\Entity\User;
use App\Message\SendVerificationEmailMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * Handles the public registration payload to create a User entity.
 */
final class CreateUserProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly MessageBusInterface $messageBus,
        #[Autowire(service: 'limiter.user_registration')]
        private readonly RateLimiterFactory $registrationLimiter,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        /** @var CreateUserDto $data */
        $request = $this->requestStack->getCurrentRequest();
        $clientId = $request?->getClientIp() ?? 'anonymous';
        $limit = $this->registrationLimiter->create($clientId)->consume(1);
        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();
            $retryAfterSeconds = $retryAfter ? max(1, $retryAfter->getTimestamp() - time()) : null;

            throw new TooManyRequestsHttpException(
                $retryAfterSeconds !== null ? (string) $retryAfterSeconds : null,
                'Too many registrations. Please try again later.'
            );
        }

        $user = new User();
        $user->setEmail(mb_strtolower(trim($data->email)));
        $user->setPlainPassword($data->password);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPassword($hashedPassword);
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false);
        $user->setConfirmationToken(bin2hex(random_bytes(32)));
        $user->setConfirmationSentAt(new \DateTimeImmutable());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Send the email asynchronously via Messenger.
        $this->messageBus->dispatch(new SendVerificationEmailMessage(
            $user->getEmail(),
            (string) $user->getConfirmationToken()
        ));

        return $user;
    }

}
