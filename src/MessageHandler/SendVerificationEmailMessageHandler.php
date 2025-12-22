<?php

namespace App\MessageHandler;

use App\Message\SendVerificationEmailMessage;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsMessageHandler]
final class SendVerificationEmailMessageHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%env(MAILER_FROM)%')]
        private readonly string $mailerFrom,
    ) {
    }

    public function __invoke(SendVerificationEmailMessage $message): void
    {
        // Build a signed verification URL from the token carried by the message.
        $verificationUrl = $this->urlGenerator->generate(
            'verify_email',
            ['token' => $message->token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Render and send the verification email.
        $email = (new TemplatedEmail())
            ->from($this->mailerFrom)
            ->to($message->email)
            ->subject('Confirm your account')
            ->htmlTemplate('emails/verify_email.html.twig')
            ->context([
                'recipientEmail' => $message->email,
                'verificationUrl' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }
}
