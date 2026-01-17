<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FilingType extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'jurisdiction_id',
        'code',
        'name',
        'description',
    ];

    /**
     * Get the jurisdiction for this filing type.
     */
    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(Jurisdiction::class);
    }

    /**
     * Get the filings of this type.
     */
    public function filings(): HasMany
    {
        return $this->hasMany(Filing::class);
    }
}
