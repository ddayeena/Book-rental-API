<?php

namespace App\Enums;

enum BookLanguage: string
{
    case UK = 'uk';
    case EN = 'en';
    case PL = 'pl';
    case DE = 'de';

    public function label(): string
    {
        return match($this) {
            self::UK => 'Українська',
            self::EN => 'English',
            self::PL => 'Polski',
            self::DE => 'Deutsch',
        };
    }
}