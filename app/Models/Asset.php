<?php

namespace App\Models;

use App\Enums\Finance\AssetType;
use App\Enums\Finance\OwnershipStructure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Asset extends Model
{
    use HasFactory;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'type',
        'entity_id',
        'address_id',
        'ownership_structure',
        'acquisition_date',
        'acquisition_cost',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AssetType::class,
            'ownership_structure' => OwnershipStructure::class,
            'acquisition_date' => 'date',
            'acquisition_cost' => 'decimal:2',
        ];
    }

    /**
     * Get the entity that owns this asset.
     */
    public function entity(): BelongsTo
    {
        return $this->belongsTo(Entity::class);
    }

    /**
     * Get the address for this asset.
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * Get year-end values for this asset.
     */
    public function yearEndValues(): HasMany
    {
        return $this->hasMany(YearEndValue::class);
    }

    /**
     * Get the documents linked to this asset.
     */
    public function documents(): MorphToMany
    {
        return $this->morphToMany(Document::class, 'documentable')->withTimestamps();
    }

    /**
     * Get transaction categories related to rental income for this asset.
     */
    public function getRentalIncomeCategories()
    {
        return TransactionCategory::query()
            ->where('jurisdiction_id', $this->entity->jurisdiction_id)
            ->where('income_or_expense', 'income')
            ->where('name', 'like', '%rental%')
            ->get();
    }

    /**
     * Get transaction categories related to rental expenses for this asset.
     */
    public function getRentalExpenseCategories()
    {
        return TransactionCategory::query()
            ->where('jurisdiction_id', $this->entity->jurisdiction_id)
            ->where('income_or_expense', 'expense')
            ->where('name', 'like', '%rental%')
            ->get();
    }
}
