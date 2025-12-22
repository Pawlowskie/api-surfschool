<?php

namespace App\MessageHandler;

use App\Enum\BookingStatus;
use App\Message\SendBookingReminderEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
final class SendBookingReminderEmailMessageHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $mailerFrom,
    ) {
    }

    public function __invoke(SendBookingReminderEmailMessage $message): void
    {
        $template = 'emails/booking_reminder_confirmed.html.twig';
        $subject = 'Rappel : votre session de surf approche';
        $context = [
            'recipientEmail' => $message->email,
            'sessionTitle' => $message->sessionTitle,
            'sessionStart' => $message->sessionStart,
        ];

        if ($message->status === BookingStatus::Pending) {
            $template = 'emails/booking_reminder_pending.html.twig';
            $subject = 'Confirmez votre réservation avant la session';
            $confirmationUrl = null;

            if ($message->token) {
                $confirmationUrl = $this->urlGenerator->generate(
                    'confirm_booking',
                    ['token' => $message->token],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
            }

            $context['confirmationUrl'] = $confirmationUrl;
        }

        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($message->email)
            ->subject($subject)
            ->htmlTemplate($template)
            ->context($context);

        $this->mailer->send($email);
    }
}
