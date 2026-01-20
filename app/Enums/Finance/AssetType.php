<?php

namespace App\Enums\Finance;

enum AssetType: string
{
    case Residential = 'residential';
    case Commercial = 'commercial';
    case Land = 'land';
    case Vehicle = 'vehicle';

    public function label(): string
    {
        return match ($this) {
            self::Residential => 'Residential Property',
            self::Commercial => 'Commercial Property',
            self::Land => 'Land',
            self::Vehicle => 'Vehicle',
        };
    }
}
