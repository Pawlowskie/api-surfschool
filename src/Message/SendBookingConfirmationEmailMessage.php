<?php

namespace App\Message;

final class SendBookingConfirmationEmailMessage
{
    public function __construct(
        public readonly string $email,
        public readonly string $token,
        public readonly string $sessionTitle,
        public readonly string $sessionStart,
    ) {
    }
}
