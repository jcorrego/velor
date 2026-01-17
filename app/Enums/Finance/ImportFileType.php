<?php

namespace App\Enums\Finance;

enum ImportFileType: string
{
    case CSV = 'csv';
    case PDF = 'pdf';

    public function label(): string
    {
        return match ($this) {
            self::CSV => 'CSV',
            self::PDF => 'PDF',
        };
    }
}
