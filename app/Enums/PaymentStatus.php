<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING  = 'pending';
    case PAID     = 'paid';
    case FAILED   = 'failed';
    case REFUNDED = 'refunded';
    case REFUND_PENDING = 'refund_pending';

    public function label(): string
    {
        return match ($this) {
            self::PENDING  => 'Очікує оплати',
            self::PAID     => 'Оплачено',
            self::FAILED   => 'Помилка оплати',
            self::REFUNDED => 'Повернено',
            self::REFUND_PENDING => 'Очікує повернення коштів',
        };
    }
}