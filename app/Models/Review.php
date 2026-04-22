<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['title', 'description', 'status'])]
class Review extends Model
{
    use HasFactory;

    /**
     * Get the user that owns the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the articles for the review.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Get the members for the review.
     */
    public function members(): HasMany
    {
        return $this->hasMany(ReviewMember::class);
    }

    /**
     * Get all users associated with this review (including creator).
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'review_members', 'review_id', 'user_id')
            ->withPivot('role', 'invited_at', 'accepted_at')
            ->withTimestamps();
    }

    /**
     * Check if user is a member of this review.
     */
    public function hasMember(User $user): bool
    {
        return $this->members()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('email', $user->email);
            })
            ->exists();
    }

    /**
     * Get member role for a user.
     */
    public function getMemberRole(User $user): ?string
    {
        return $this->members()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('email', $user->email);
            })
            ->value('role');
    }

    /**
     * Check if user is coordinator.
     */
    public function isCoordinator(User $user): bool
    {
        return $this->user_id === $user->id || 
               $this->getMemberRole($user) === 'coordinator';
    }
}
