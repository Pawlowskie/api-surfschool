<?php

namespace App\Message;

// Message DTO for async verification email sending.
final class SendVerificationEmailMessage
{
    public function __construct(
        public readonly string $email,
        public readonly string $token,
    ) {
    }
}
