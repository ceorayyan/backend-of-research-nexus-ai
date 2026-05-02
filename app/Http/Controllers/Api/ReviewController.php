<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    /**
     * Create a new review.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:draft,active,completed,archived',
        ]);

        $review = $request->user()->reviews()->create($validated);

        return response()->json([
            'message' => 'Review created successfully',
            'data' => $review->load('user'),
        ], 201);
    }

    /**
     * Get all reviews for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get reviews created by user and reviews they're members of (by user_id or email)
        // Only load user and article count, not all articles
        $reviews = Review::where('user_id', $user->id)
            ->orWhereHas('members', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhere('email', $user->email);
            })
            ->with(['user:id,name,email'])
            ->withCount('articles')
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Back-compat response shape expected by tests/UI:
        // - `articles` as a count (not a full list)
        // - `members` as a count
        $reviews->getCollection()->transform(function ($review) {
            $review->articles = $review->articles_count ?? 0;
            $review->members = $review->members_count ?? 0;
            unset($review->articles_count, $review->members_count);
            return $review;
        });

        return response()->json($reviews);
    }

    /**
     * Get a specific review.
     */
    public function show(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has access to this review
        if (!$this->userCanAccessReview($user, $review)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only load user and members, not all articles
        $review->load(['user:id,name,email', 'members.user:id,name,email']);
        $review->loadCount('articles');

        return response()->json([
            'id' => $review->id,
            'title' => $review->title,
            'description' => $review->description,
            'status' => $review->status,
            'blind_mode' => $review->blind_mode,
            'user' => $review->user,
            'articles' => $review->articles_count ?? 0,
            'members' => $review->members,
        ]);
    }

    /**
     * Update a review.
     */
    public function update(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        // Only creator or coordinator can update
        if (!$review->isCoordinator($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:draft,active,completed,archived',
        ]);

        $review->update($validated);

        return response()->json([
            'message' => 'Review updated successfully',
            'data' => $review->load('user'),
        ]);
    }

    /**
     * Delete a review.
     */
    public function destroy(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        // Only creator can delete
        if ($review->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->delete();

        return response()->json(['message' => 'Review deleted successfully']);
    }

    /**
     * Toggle blind mode for a review (owner only).
     */
    public function toggleBlindMode(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        // Only owner can toggle blind mode
        if ($review->user_id !== $user->id) {
            \Log::warning('Permission denied: Blind mode toggle', [
                'user_id' => $user->id,
                'review_id' => $review->id,
                'action' => 'toggle_blind_mode',
                'user_role' => $review->getMemberRole($user) ?? 'non-member',
                'ip_address' => $request->ip(),
            ]);
            return response()->json(['message' => 'Only the review owner can toggle blind mode'], 403);
        }

        $oldValue = $review->blind_mode;
        $review->update(['blind_mode' => !$review->blind_mode]);

        \Log::info('Blind mode toggled', [
            'user_id' => $user->id,
            'review_id' => $review->id,
            'blind_mode' => $review->blind_mode,
            'previous_value' => $oldValue,
            'timestamp' => now(),
        ]);

        return response()->json([
            'message' => 'Blind mode ' . ($review->blind_mode ? 'enabled' : 'disabled'),
            'data' => [
                'blind_mode' => $review->blind_mode,
            ],
        ]);
    }

    /**
     * Check if user can access review.
     */
    private function userCanAccessReview(User $user, Review $review): bool
    {
        return $review->user_id === $user->id || $review->hasMember($user);
    }
}
