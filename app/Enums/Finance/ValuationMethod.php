<?php

namespace App\Enums\Finance;

enum ValuationMethod: string
{
    case Appraisal = 'appraisal';
    case MarketComparable = 'market_comparable';
    case TaxAssessed = 'tax_assessed';

    public function label(): string
    {
        return match ($this) {
            self::Appraisal => 'Professional Appraisal',
            self::MarketComparable => 'Market Comparable',
            self::TaxAssessed => 'Tax Assessed Value',
        };
    }
}
