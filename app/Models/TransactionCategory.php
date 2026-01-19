<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionCategory extends Model
{
    use HasFactory;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'jurisdiction_id',
        'income_or_expense',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'income_or_expense' => 'string',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Get the jurisdiction for this category.
     */
    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(Jurisdiction::class);
    }

    /**
     * Get all transactions in this category.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'category_id');
    }

    /**
     * Get all tax mappings for this category.
     */
    public function taxMappings(): HasMany
    {
        return $this->hasMany(CategoryTaxMapping::class, 'category_id');
    }

    /**
     * Alias for tax mappings to match Livewire expectations.
     */
    public function categoryTaxMappings(): HasMany
    {
        return $this->hasMany(CategoryTaxMapping::class, 'category_id');
    }
}
