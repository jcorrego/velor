<?php

namespace App\Models;

use App\Enums\Finance\RelatedPartyType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelatedPartyTransaction extends Model
{
    /**
     * @var array<string>
     */
    protected $fillable = [
        'transaction_date',
        'amount',
        'type',
        'owner_id',
        'account_id',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => RelatedPartyType::class,
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Get the user (owner) for this related party transaction.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the account for this related party transaction.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
