<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurisdiction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'iso_code',
        'timezone',
        'default_currency',
        'tax_year_start_month',
        'tax_year_start_day',
    ];

    /**
     * Get the user profiles for this jurisdiction.
     */
    public function userProfiles(): HasMany
    {
        return $this->hasMany(UserProfile::class);
    }

    /**
     * Get the residency periods for this jurisdiction.
     */
    public function residencyPeriods(): HasMany
    {
        return $this->hasMany(ResidencyPeriod::class);
    }

    /**
     * Get the tax years for this jurisdiction.
     */
    public function taxYears(): HasMany
    {
        return $this->hasMany(TaxYear::class);
    }

    /**
     * Get the filing types for this jurisdiction.
     */
    public function filingTypes(): HasMany
    {
        return $this->hasMany(FilingType::class);
    }

    /**
     * Get the entities for this jurisdiction.
     */
    public function entities(): HasMany
    {
        return $this->hasMany(Entity::class);
    }
}
