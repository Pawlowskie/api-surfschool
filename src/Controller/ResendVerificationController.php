<?php

namespace App\Controller;

use App\Entity\User;
use App\Message\SendVerificationEmailMessage;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

final class ResendVerificationController
{
    private const RESEND_COOLDOWN = 'PT5M';

    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('/api/resend-verification', name: 'resend_verification', methods: ['POST'])]
    public function __invoke(): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'No authenticated user.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->isVerified()) {
            return new JsonResponse(['message' => 'Account already verified.'], Response::HTTP_BAD_REQUEST);
        }

        $sentAt = $user->getConfirmationSentAt();
        if ($sentAt instanceof DateTimeImmutable) {
            $nextAllowed = $sentAt->add(new DateInterval(self::RESEND_COOLDOWN));
            if (new DateTimeImmutable() < $nextAllowed) {
                return new JsonResponse(
                    ['message' => 'Please wait before requesting another verification email.'],
                    Response::HTTP_TOO_MANY_REQUESTS
                );
            }
        }

        if (!$user->getConfirmationToken()) {
            $user->setConfirmationToken(bin2hex(random_bytes(32)));
        }

        $user->setConfirmationSentAt(new DateTimeImmutable());
        $this->entityManager->flush();

        $this->messageBus->dispatch(new SendVerificationEmailMessage(
            $user->getEmail(),
            (string) $user->getConfirmationToken()
        ));

        return new JsonResponse(['message' => 'Verification email sent.'], Response::HTTP_OK);
    }
}
