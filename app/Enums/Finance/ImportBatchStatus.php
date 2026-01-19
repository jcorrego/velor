<?php

namespace App\Enums\Finance;

enum ImportBatchStatus: string
{
    case Pending = 'pending';
    case Applied = 'applied';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::Applied => 'Applied',
            self::Rejected => 'Rejected',
        };
    }
}
