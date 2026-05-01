<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'authors', 'abstract', 'url', 'file_path', 'fulltext_pdf_path', 'status', 'screening_decision_by', 'screening_notes', 'keywords', 'labels', 'exclusion_reasons', 'journal', 'year', 'fulltext_status', 'fulltext_decision_by', 'fulltext_notes', 'fulltext_labels', 'fulltext_exclusion_reasons'])]
class Article extends Model
{
    use HasFactory;

    protected $casts = [
        'keywords' => 'array',
        'labels' => 'array',
        'exclusion_reasons' => 'array',
        'fulltext_labels' => 'array',
        'fulltext_exclusion_reasons' => 'array',
    ];

    protected $appends = ['screening_decision'];

    /**
     * Boot the model and set up event listeners.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate reference_id when creating a new article
        static::creating(function ($article) {
            if (empty($article->reference_id)) {
                // Get the next ID by finding the max ID and adding 1
                $maxId = static::max('id') ?? 0;
                $nextId = $maxId + 1;
                $article->reference_id = 'STX-' . str_pad($nextId, 8, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * Get the screening decision (alias for status).
     */
    public function getScreeningDecisionAttribute(): ?string
    {
        return $this->status;
    }

    /**
     * Get the review that owns the article.
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Get duplicates where this article is the first article.
     */
    public function duplicatesAsArticle1(): HasMany
    {
        return $this->hasMany(Duplicate::class, 'article1_id');
    }

    /**
     * Get duplicates where this article is the second article.
     */
    public function duplicatesAsArticle2(): HasMany
    {
        return $this->hasMany(Duplicate::class, 'article2_id');
    }
}
