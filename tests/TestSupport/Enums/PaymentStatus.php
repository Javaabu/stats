<?php

namespace Javaabu\Stats\Tests\TestSupport\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Awaiting Payment',
            self::PAID => 'Paid',
        };
    }
}
