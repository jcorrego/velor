<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Document extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'jurisdiction_id',
        'tax_year_id',
        'title',
        'original_name',
        'stored_path',
        'storage_disk',
        'mime_type',
        'size',
        'is_legal',
        'extracted_text',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_legal' => 'boolean',
        ];
    }

    /**
     * Get the user who owns the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the jurisdiction for this document.
     */
    public function jurisdiction(): BelongsTo
    {
        return $this->belongsTo(Jurisdiction::class);
    }

    /**
     * Get the tax year for this document.
     */
    public function taxYear(): BelongsTo
    {
        return $this->belongsTo(TaxYear::class);
    }

    /**
     * Get the tags for this document.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(DocumentTag::class, 'document_tag')->withTimestamps();
    }

    /**
     * Get the entities linked to this document.
     */
    public function entities(): MorphToMany
    {
        return $this->morphedByMany(Entity::class, 'documentable')->withTimestamps();
    }

    /**
     * Get the assets linked to this document.
     */
    public function assets(): MorphToMany
    {
        return $this->morphedByMany(Asset::class, 'documentable')->withTimestamps();
    }

    /**
     * Get the transactions linked to this document.
     */
    public function transactions(): MorphToMany
    {
        return $this->morphedByMany(Transaction::class, 'documentable')->withTimestamps();
    }

    /**
     * Get the filings linked to this document.
     */
    public function filings(): MorphToMany
    {
        return $this->morphedByMany(Filing::class, 'documentable')->withTimestamps();
    }
}
