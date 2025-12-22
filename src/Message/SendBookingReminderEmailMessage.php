<?php

namespace App\Message;

use App\Enum\BookingStatus;

final class SendBookingReminderEmailMessage
{
    public function __construct(
        public readonly string $email,
        public readonly BookingStatus $status,
        public readonly string $sessionTitle,
        public readonly string $sessionStart,
        public readonly ?string $token = null,
    ) {
    }
}
