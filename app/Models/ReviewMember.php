<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['review_id', 'user_id', 'email', 'role', 'status', 'invited_at', 'accepted_at'])]
class ReviewMember extends Model
{
    use HasFactory;

    /**
     * Get the review that owns the member.
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Get the user that is a member.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if member has accepted the invitation.
     */
    public function hasAccepted(): bool
    {
        return $this->accepted_at !== null || $this->status === 'accepted';
    }

    /**
     * Mark member as accepted.
     */
    public function accept(): void
    {
        $this->update([
            'accepted_at' => now(),
            'status' => 'accepted',
        ]);
    }

    /**
     * Check if invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
