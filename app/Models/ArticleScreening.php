<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['article_id', 'user_id', 'decision', 'notes', 'labels', 'exclusion_reasons'])]
class ArticleScreening extends Model
{
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'labels' => 'array',
        'exclusion_reasons' => 'array',
    ];

    /**
     * Get the article that owns the screening.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the user that made the screening decision.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
