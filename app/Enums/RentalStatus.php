<?php

namespace App\Enums;

enum RentalStatus: string
{
    case PENDING   = 'pending';
    case ACTIVE    = 'active';
    case OVERDUE   = 'overdue';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case LOST      = 'lost';

    public function label(): string
    {
        return match($this) {
            self::PENDING   => 'Очікує видачі',
            self::ACTIVE    => 'Активна (на руках)',
            self::OVERDUE   => 'Протермінована',
            self::COMPLETED => 'Завершена',
            self::CANCELLED => 'Скасована',
            self::LOST      => 'Втрачена',
        };
    }
}