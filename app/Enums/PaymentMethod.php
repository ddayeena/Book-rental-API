<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PAY_ONLINE    = 'pay_online';
    case PAY_ON_PICKUP = 'pay_on_pickup';

    public function label(): string
    {
        return match ($this) {
            self::PAY_ONLINE    => 'Онлайн-оплата',
            self::PAY_ON_PICKUP => 'Оплата при отриманні',
        };
    }
}