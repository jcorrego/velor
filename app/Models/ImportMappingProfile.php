<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportMappingProfile extends Model
{
    use HasFactory;

    /**
     * @var array<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'column_mapping',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'column_mapping' => 'json',
        ];
    }

    /**
     * Get the account this profile belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
