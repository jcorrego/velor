<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    /**
     * @var array<string>
     */
    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the FX rates from this currency.
     */
    public function fxRatesFrom(): HasMany
    {
        return $this->hasMany(FxRate::class, 'currency_from_id');
    }

    /**
     * Get the FX rates to this currency.
     */
    public function fxRatesTo(): HasMany
    {
        return $this->hasMany(FxRate::class, 'currency_to_id');
    }

    /**
     * Get all accounts in this currency.
     */
    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Get all asset valuations in this currency.
     */
    public function assetValuations(): HasMany
    {
        return $this->hasMany(AssetValuation::class);
    }
}
