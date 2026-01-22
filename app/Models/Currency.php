<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    use HasFactory;

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
     * Get all assets acquired in this currency.
     */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'acquisition_currency_id');
    }

    /**
     * Get all transactions that use this currency as the original currency.
     */
    public function originalTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'original_currency_id');
    }

    /**
     * Get all transactions that use this currency as the converted currency.
     */
    public function convertedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'converted_currency_id');
    }

    public function isInUse(): bool
    {
        return $this->accounts()->exists()
            || $this->assets()->exists()
            || $this->originalTransactions()->exists()
            || $this->convertedTransactions()->exists()
            || $this->fxRatesFrom()->exists()
            || $this->fxRatesTo()->exists();
    }
}
