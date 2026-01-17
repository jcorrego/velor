<?php

namespace App\Models;

use App\Enums\Finance\ImportFileType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionImport extends Model
{
    /**
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'file_type',
        'file_name',
        'parsed_count',
        'matched_count',
        'imported_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_type' => ImportFileType::class,
            'imported_at' => 'timestamp',
            'parsed_count' => 'integer',
            'matched_count' => 'integer',
        ];
    }

    /**
     * Get the account for this transaction import.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
