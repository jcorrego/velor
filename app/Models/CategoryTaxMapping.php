<?php

namespace App\Models;

use App\Enums\Finance\TaxFormCode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategoryTaxMapping extends Model
{
    use HasFactory;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'category_id',
        'tax_form_code',
        'line_item',
        'country',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tax_form_code' => TaxFormCode::class,
        ];
    }

    /**
     * Get the transaction category this mapping belongs to.
     */
    public function transactionCategory(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class, 'category_id');
    }
}
