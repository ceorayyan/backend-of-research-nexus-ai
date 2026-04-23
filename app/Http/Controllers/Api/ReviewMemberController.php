<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use App\Mail\ReviewInvitation;

class ReviewMemberController extends Controller
{
    /**
     * Invite a member to a review.
     */
    public function invite(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        // Only creator or coordinator can invite
        if (!$review->isCoordinator($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:reviewer,coordinator,observer',
            'message' => 'nullable|string',
        ]);

        // Check if user exists
        $invitedUser = User::where('email', $validated['email'])->first();

        // Check if already invited (by email or user_id)
        $existingInvitation = ReviewMember::where('review_id', $review->id)
            ->where(function ($query) use ($validated, $invitedUser) {
                $query->where('email', $validated['email']);
                if ($invitedUser) {
                    $query->orWhere('user_id', $invitedUser->id);
                }
            })
            ->first();

        if ($existingInvitation) {
            return response()->json(['message' => 'User is already invited or a member'], 400);
        }

        // Check if user is the creator
        if ($invitedUser && $review->user_id === $invitedUser->id) {
            return response()->json(['message' => 'Cannot invite the review creator'], 400);
        }

        // Create invitation
        $memberData = [
            'review_id' => $review->id,
            'email' => $validated['email'],
            'role' => $validated['role'],
            'invited_at' => now(),
            'status' => 'pending',
        ];

        // If user exists, link to user_id
        if ($invitedUser) {
            $memberData['user_id'] = $invitedUser->id;
        }

        $member = ReviewMember::create($memberData);

        // Send invitation email
        try {
            if ($invitedUser) {
                Mail::to($invitedUser->email)->send(new ReviewInvitation(
                    $review,
                    $user,
                    $invitedUser,
                    $validated['message'] ?? null
                ));
            } else {
                // Send invitation to non-registered user
                Mail::to($validated['email'])->send(new ReviewInvitation(
                    $review,
                    $user,
                    null,
                    $validated['message'] ?? null
                ));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send invitation email: ' . $e->getMessage());
        }

        return response()->json([
            'message' => $invitedUser 
                ? 'Member invited successfully and email sent' 
                : 'Invitation sent. User will need to sign up first.',
            'data' => $member->load('user'),
        ], 201);
    }

    /**
     * Get members of a review.
     */
    public function index(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has access to this review
        if ($review->user_id !== $user->id && !$review->hasMember($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Only select necessary columns for better performance
        $members = $review->members()
            ->with('user:id,name,email')
            ->select('id', 'review_id', 'user_id', 'email', 'role', 'status', 'invited_at', 'accepted_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get(); // Use get() instead of paginate() since members list is usually small

        return response()->json($members);
    }

    /**
     * Accept invitation to a review.
     */
    public function accept(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        $member = $review->members()
            ->where('user_id', $user->id)
            ->first();

        if (!$member) {
            return response()->json(['message' => 'Not invited to this review'], 404);
        }

        $member->accept();

        return response()->json([
            'message' => 'Invitation accepted',
            'data' => $member->load('user'),
        ]);
    }

    /**
     * Remove a member from a review.
     */
    public function destroy(Review $review, ReviewMember $member, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if member belongs to this review
        if ($member->review_id !== $review->id) {
            return response()->json(['message' => 'Member not found in this review'], 404);
        }

        // Only creator or coordinator can remove members
        if (!$review->isCoordinator($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Cannot remove the creator
        if ($member->user_id === $review->user_id) {
            return response()->json(['message' => 'Cannot remove the review creator'], 400);
        }

        $member->delete();

        return response()->json(['message' => 'Member removed successfully']);
    }

    /**
     * Update member role.
     */
    public function updateRole(Review $review, ReviewMember $member, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if member belongs to this review
        if ($member->review_id !== $review->id) {
            return response()->json(['message' => 'Member not found in this review'], 404);
        }

        // Only creator or coordinator can update roles
        if (!$review->isCoordinator($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'role' => 'required|in:reviewer,coordinator,observer',
        ]);

        $member->update(['role' => $validated['role']]);

        return response()->json([
            'message' => 'Member role updated successfully',
            'data' => $member->load('user'),
        ]);
    }
}
