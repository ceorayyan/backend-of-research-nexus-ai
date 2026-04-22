<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'authors', 'abstract', 'url', 'file_path'])]
class Article extends Model
{
    use HasFactory;

    /**
     * Get the review that owns the article.
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
