<?php

namespace App\Enums\Finance;

enum AccountType: string
{
    case Checking = 'checking';
    case Savings = 'savings';
    case Digital = 'digital';

    public function label(): string
    {
        return match ($this) {
            self::Checking => 'Checking',
            self::Savings => 'Savings',
            self::Digital => 'Digital Wallet',
        };
    }
}
