<?php

namespace App\MessageHandler;

use App\Message\SendBookingCancellationEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SendBookingCancellationEmailMessageHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $mailerFrom,
    ) {
    }

    public function __invoke(SendBookingCancellationEmailMessage $message): void
    {
        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($message->email)
            ->subject('Réservation annulée (non confirmée)')
            ->htmlTemplate('emails/booking_cancelled.html.twig')
            ->context([
                'recipientEmail' => $message->email,
                'sessionTitle' => $message->sessionTitle,
                'sessionStart' => $message->sessionStart,
            ]);

        $this->mailer->send($email);
    }
}
