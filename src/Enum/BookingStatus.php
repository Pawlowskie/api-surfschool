<?php

namespace App\Enum;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function holdsSeat(): bool
    {
        return match ($this) {
            self::Pending, self::Confirmed => true,
            self::Cancelled => false,
        };
    }

    public function isFinal(): bool
    {
        return $this === self::Cancelled;
    }
}
