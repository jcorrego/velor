<?php

namespace App\Enums\Finance;

enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case COP = 'COP';
    case GBP = 'GBP';
    case JPY = 'JPY';

    public function label(): string
    {
        return match ($this) {
            self::USD => 'US Dollar',
            self::EUR => 'Euro',
            self::COP => 'Colombian Peso',
            self::GBP => 'British Pound',
            self::JPY => 'Japanese Yen',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::USD => '$',
            self::EUR => '€',
            self::COP => '$',
            self::GBP => '£',
            self::JPY => '¥',
        };
    }
}
