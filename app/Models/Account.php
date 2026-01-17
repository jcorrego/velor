<?php

namespace App\Models;

use App\Enums\Finance\AccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    /**
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'type',
        'currency_id',
        'entity_id',
        'opening_date',
        'closing_date',
        'integration_metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'opening_date' => 'date',
            'closing_date' => 'date',
            'integration_metadata' => 'json',
        ];
    }

    /**
     * Get the currency for this account.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the entity that owns this account.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Get all transactions in this account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get all transaction imports for this account.
     */
    public function transactionImports(): HasMany
    {
        return $this->hasMany(TransactionImport::class);
    }

    /**
     * Get all related party transactions for this account.
     */
    public function relatedPartyTransactions(): HasMany
    {
        return $this->hasMany(RelatedPartyTransaction::class);
    }
}
