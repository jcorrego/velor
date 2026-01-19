<?php

namespace App\Models;

use App\Enums\Finance\ImportBatchStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportBatch extends Model
{
    use HasFactory;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'status',
        'proposed_transactions',
        'transaction_count',
        'rejection_reason',
        'approved_by',
        'approved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportBatchStatus::class,
            'proposed_transactions' => 'json',
            'transaction_count' => 'integer',
            'approved_at' => 'timestamp',
        ];
    }

    /**
     * Get the account this batch belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the user who approved this batch.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
