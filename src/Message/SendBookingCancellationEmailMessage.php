<?php

namespace App\Message;

final class SendBookingCancellationEmailMessage
{
    public function __construct(
        public readonly string $email,
        public readonly string $sessionTitle,
        public readonly string $sessionStart,
    ) {
    }
}
