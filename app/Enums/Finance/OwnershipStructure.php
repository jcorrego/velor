<?php

namespace App\Enums\Finance;

enum OwnershipStructure: string
{
    case Direct = 'direct';
    case Individual = 'individual';
    case LLC = 'llc';
    case Partnership = 'partnership';
    case Corporation = 'corporation';

    public function label(): string
    {
        return match ($this) {
            self::Direct => 'Direct',
            self::Individual => 'Individual',
            self::LLC => 'LLC',
            self::Partnership => 'Partnership',
            self::Corporation => 'Corporation',
        };
    }
}
