<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'authors', 'abstract', 'url', 'file_path', 'status', 'screening_notes', 'keywords', 'journal', 'year'])]
class Article extends Model
{
    use HasFactory;

    protected $casts = [
        'keywords' => 'array',
    ];

    protected $appends = ['screening_decision'];

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
}
