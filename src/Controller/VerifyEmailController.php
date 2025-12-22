<?php

namespace App\Controller;

use App\Repository\UserRepository;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class VerifyEmailController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $tokenTtl = 'P1D',
    ) {
    }

    #[Route('/api/verify-email/{token}', name: 'verify_email', methods: ['GET'])]
    public function __invoke(Request $request, string $token): Response
    {
        $user = $this->userRepository->findOneBy(['confirmationToken' => $token]);
        if (!$user) {
            return new JsonResponse(['message' => 'Invalid token.'], Response::HTTP_NOT_FOUND);
        }

        $sentAt = $user->getConfirmationSentAt();
        if ($sentAt instanceof DateTimeImmutable) {
            $expiresAt = $sentAt->add(new DateInterval($this->tokenTtl));
            if (new DateTimeImmutable() > $expiresAt) {
                return new JsonResponse(['message' => 'Confirmation link has expired.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $user->setIsVerified(true);
        $user->setConfirmationToken(null);
        $user->setConfirmationSentAt(null);
        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Account verified.'], Response::HTTP_OK);
    }
}
