<?php

namespace App\MessageHandler;

use App\Message\SendBookingConfirmationEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
final class SendBookingConfirmationEmailMessageHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $mailerFrom,
    ) {
    }

    public function __invoke(SendBookingConfirmationEmailMessage $message): void
    {
        $confirmationUrl = $this->urlGenerator->generate(
            'confirm_booking',
            ['token' => $message->token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($message->email)
            ->subject('Confirmez votre réservation')
            ->htmlTemplate('emails/booking_confirmation.html.twig')
            ->context([
                'recipientEmail' => $message->email,
                'confirmationUrl' => $confirmationUrl,
                'sessionTitle' => $message->sessionTitle,
                'sessionStart' => $message->sessionStart,
            ]);

        $this->mailer->send($email);
    }
}
