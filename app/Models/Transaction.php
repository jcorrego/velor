<?php

namespace App\Models;

use App\Enums\Finance\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'transaction_date',
        'account_id',
        'type',
        'original_amount',
        'original_currency_id',
        'converted_amount',
        'converted_currency_id',
        'fx_rate',
        'fx_source',
        'category_id',
        'counterparty_name',
        'description',
        'tags',
        'reconciled_at',
        'import_source',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'transaction_date' => 'date',
            'original_amount' => 'decimal:2',
            'converted_amount' => 'decimal:2',
            'fx_rate' => 'decimal:8',
            'tags' => 'json',
            'reconciled_at' => 'timestamp',
        ];
    }

    /**
     * Get the account this transaction belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the category for this transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }

    /**
     * Get the original currency for this transaction.
     */
    public function originalCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'original_currency_id');
    }

    /**
     * Get the converted currency for this transaction.
     */
    public function convertedCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'converted_currency_id');
    }
}
