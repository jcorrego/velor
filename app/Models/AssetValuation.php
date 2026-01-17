<?php

namespace App\Models;

use App\Enums\Finance\ValuationMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetValuation extends Model
{
    /**
     * @var array<string>
     */
    protected $fillable = [
        'asset_id',
        'amount',
        'valuation_date',
        'method',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'valuation_date' => 'date',
            'method' => ValuationMethod::class,
        ];
    }

    /**
     * Get the asset being valued.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }
}
