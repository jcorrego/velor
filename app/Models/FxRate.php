<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FxRate extends Model
{
    use HasFactory;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'currency_from_id',
        'currency_to_id',
        'rate',
        'rate_date',
        'source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'decimal:8',
            'rate_date' => 'date',
        ];
    }

    /**
     * Get the currency converting from.
     */
    public function currencyFrom(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_from_id');
    }

    /**
     * Get the currency converting to.
     */
    public function currencyTo(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_to_id');
    }
}
