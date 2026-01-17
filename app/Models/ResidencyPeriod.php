<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidencyPeriod extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'jurisdiction_id',
        'start_date',
        'end_date',
        'is_fiscal_residence',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_fiscal_residence' => 'boolean',
        ];
    }

    /**
     * Get the user for this residency period.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the jurisdiction for this residency period.
     */
    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(Jurisdiction::class);
    }

    /**
     * Get fiscal residence jurisdiction for a user in a given tax year.
     * Returns the jurisdiction where user spent ≥183 days.
     */
    public static function getFiscalResidenceForYear(int $userId, int $year): ?Jurisdiction
    {
        $yearStart = Carbon::create($year, 1, 1);
        $yearEnd = Carbon::create($year, 12, 31);

        $periods = self::with('jurisdiction')
            ->where('user_id', $userId)
            ->where(function ($query) use ($yearStart, $yearEnd) {
                $query->where(function ($q) use ($yearStart, $yearEnd) {
                    $q->where('start_date', '<=', $yearEnd)
                        ->where(function ($q2) use ($yearStart) {
                            $q2->whereNull('end_date')
                                ->orWhere('end_date', '>=', $yearStart);
                        });
                });
            })
            ->get();

        $daysPerJurisdiction = [];

        foreach ($periods as $period) {
            $periodStart = $period->start_date->max($yearStart);
            $periodEnd = $period->end_date ? $period->end_date->min($yearEnd) : $yearEnd;

            $days = $periodStart->diffInDays($periodEnd) + 1;

            $jurisdictionId = $period->jurisdiction_id;
            if (! isset($daysPerJurisdiction[$jurisdictionId])) {
                $daysPerJurisdiction[$jurisdictionId] = [
                    'days' => 0,
                    'jurisdiction' => $period->jurisdiction,
                ];
            }

            $daysPerJurisdiction[$jurisdictionId]['days'] += $days;
        }

        // Find jurisdiction with ≥183 days
        foreach ($daysPerJurisdiction as $data) {
            if ($data['days'] >= 183) {
                return $data['jurisdiction'];
            }
        }

        return null;
    }
}
