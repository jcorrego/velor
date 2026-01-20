<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DescriptionCategoryRule extends Model
{
    use HasFactory;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'jurisdiction_id',
        'category_id',
        'description_pattern',
        'counterparty',
        'notes',
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
     * Get the jurisdiction for this rule.
     */
    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(Jurisdiction::class);
    }

    /**
     * Get the category for this rule.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TransactionCategory::class);
    }
}
