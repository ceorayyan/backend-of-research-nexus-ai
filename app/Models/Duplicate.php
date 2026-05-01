<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Duplicate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'review_id',
        'article1_id',
        'article2_id',
        'similarity_score',
        'detection_reason',
        'status',
        'marked_by_user_id',
        'kept_article_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'similarity_score' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'article1_title',
        'article1_authors',
        'article1_created_at',
        'article1_labels',
        'article1_screening_notes',
        'article2_title',
        'article2_authors',
        'article2_created_at',
        'article2_labels',
        'article2_screening_notes',
        'loser_article_id',
    ];

    /**
     * Get the review that owns the duplicate.
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Get the first article in the duplicate pair.
     */
    public function article1(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article1_id');
    }

    /**
     * Get the second article in the duplicate pair.
     */
    public function article2(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article2_id');
    }

    /**
     * Get the user who marked this duplicate.
     */
    public function markedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by_user_id');
    }

    /**
     * Scope a query to only include duplicates with a specific status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include unresolved duplicates.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnresolved($query)
    {
        return $query->where('status', 'unresolved');
    }

    /**
     * Scope a query to only include duplicates for a specific review.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $reviewId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForReview($query, int $reviewId)
    {
        return $query->where('review_id', $reviewId);
    }

    /**
     * Get the title of article 1.
     *
     * @return string|null
     */
    public function getArticle1TitleAttribute(): ?string
    {
        return $this->article1?->title;
    }

    /**
     * Get the authors of article 1.
     *
     * @return string|null
     */
    public function getArticle1AuthorsAttribute(): ?string
    {
        return $this->article1?->authors;
    }

    /**
     * Get the created_at timestamp of article 1.
     *
     * @return string|null
     */
    public function getArticle1CreatedAtAttribute(): ?string
    {
        return $this->article1?->created_at?->toISOString();
    }

    /**
     * Get the title of article 2.
     *
     * @return string|null
     */
    public function getArticle2TitleAttribute(): ?string
    {
        return $this->article2?->title;
    }

    /**
     * Get the authors of article 2.
     *
     * @return string|null
     */
    public function getArticle2AuthorsAttribute(): ?string
    {
        return $this->article2?->authors;
    }

    /**
     * Get the created_at timestamp of article 2.
     *
     * @return string|null
     */
    public function getArticle2CreatedAtAttribute(): ?string
    {
        return $this->article2?->created_at?->toISOString();
    }

    /**
     * Get the labels of article 1.
     *
     * @return array
     */
    public function getArticle1LabelsAttribute(): array
    {
        return $this->article1?->labels ?? [];
    }

    /**
     * Get the screening notes of article 1.
     *
     * @return string|null
     */
    public function getArticle1ScreeningNotesAttribute(): ?string
    {
        return $this->article1?->screening_notes;
    }

    /**
     * Get the labels of article 2.
     *
     * @return array
     */
    public function getArticle2LabelsAttribute(): array
    {
        return $this->article2?->labels ?? [];
    }

    /**
     * Get the screening notes of article 2.
     *
     * @return string|null
     */
    public function getArticle2ScreeningNotesAttribute(): ?string
    {
        return $this->article2?->screening_notes;
    }

    /**
     * Get the loser article ID (the one excluded when status = resolved).
     * Returns null if not yet resolved or both were kept.
     *
     * @return int|null
     */
    public function getLoserArticleIdAttribute(): ?int
    {
        if ($this->status !== 'resolved' || !$this->kept_article_id) {
            return null;
        }
        return $this->kept_article_id === $this->article1_id
            ? $this->article2_id
            : $this->article1_id;
    }
}
