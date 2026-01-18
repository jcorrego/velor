<?php

namespace App\Models;

use App\FilingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Filing extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'tax_year_id',
        'filing_type_id',
        'status',
        'key_metrics',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => FilingStatus::class,
            'key_metrics' => 'json',
        ];
    }

    /**
     * Get the user for this filing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tax year for this filing.
     */
    public function taxYear(): BelongsTo
    {
        return $this->belongsTo(TaxYear::class);
    }

    /**
     * Get the filing type for this filing.
     */
    public function filingType(): BelongsTo
    {
        return $this->belongsTo(FilingType::class);
    }

    /**
     * Get the documents linked to this filing.
     */
    public function documents(): MorphToMany
    {
        return $this->morphToMany(Document::class, 'documentable')->withTimestamps();
    }
}
