<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Review;
use App\Models\Article;
use App\Models\ReviewMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->reviewer = User::factory()->create();
    }

    /**
     * Test creating a review
     */
    public function test_user_can_create_review(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/reviews', [
                'title' => 'Test Review',
                'description' => 'A test review',
                'status' => 'draft',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'user_id',
                    'title',
                    'description',
                    'status',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('reviews', [
            'title' => 'Test Review',
            'user_id' => $this->user->id,
        ]);
    }

    /**
     * Test listing reviews
     */
    public function test_user_can_list_reviews(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/reviews');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'user',
                        'articles',
                        'members',
                    ],
                ],
                'current_page',
                'last_page',
                'total',
            ]);
    }

    /**
     * Test getting review details
     */
    public function test_user_can_get_review_details(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/reviews/{$review->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'title',
                'user',
                'articles',
                'members',
            ]);
    }

    /**
     * Test updating a review
     */
    public function test_creator_can_update_review(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/reviews/{$review->id}", [
                'title' => 'Updated Title',
                'status' => 'active',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'title' => 'Updated Title',
            'status' => 'active',
        ]);
    }

    /**
     * Test non-creator cannot update review
     */
    public function test_non_creator_cannot_update_review(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->reviewer)
            ->putJson("/api/reviews/{$review->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test deleting a review
     */
    public function test_creator_can_delete_review(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/reviews/{$review->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);
    }

    /**
     * Test uploading an article
     */
    public function test_user_can_upload_article(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$review->id}/articles", [
                'title' => 'Test Article',
                'authors' => 'Smith, J.',
                'abstract' => 'Test abstract',
                'url' => 'https://example.com/article',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'review_id',
                    'title',
                    'authors',
                    'abstract',
                    'url',
                ],
            ]);

        $this->assertDatabaseHas('articles', [
            'title' => 'Test Article',
            'review_id' => $review->id,
        ]);
    }

    /**
     * Test listing articles
     */
    public function test_user_can_list_articles(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);
        Article::factory()->create(['review_id' => $review->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/reviews/{$review->id}/articles");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'authors',
                    ],
                ],
            ]);
    }

    /**
     * Test inviting a member
     */
    public function test_creator_can_invite_member(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$review->id}/invite", [
                'email' => $this->reviewer->email,
                'role' => 'reviewer',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'review_id',
                    'user_id',
                    'role',
                    'invited_at',
                    'user',
                ],
            ]);

        $this->assertDatabaseHas('review_members', [
            'review_id' => $review->id,
            'user_id' => $this->reviewer->id,
            'role' => 'reviewer',
        ]);
    }

    /**
     * Test listing members
     */
    public function test_user_can_list_members(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);
        ReviewMember::factory()->create([
            'review_id' => $review->id,
            'user_id' => $this->reviewer->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/reviews/{$review->id}/members");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'role',
                        'user',
                    ],
                ],
            ]);
    }

    /**
     * Test accepting invitation
     */
    public function test_member_can_accept_invitation(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);
        ReviewMember::factory()->create([
            'review_id' => $review->id,
            'user_id' => $this->reviewer->id,
            'accepted_at' => null,
        ]);

        $response = $this->actingAs($this->reviewer)
            ->postJson("/api/reviews/{$review->id}/accept");

        $response->assertStatus(200);
        $this->assertDatabaseHas('review_members', [
            'review_id' => $review->id,
            'user_id' => $this->reviewer->id,
        ]);
        // Verify accepted_at is not null
        $member = ReviewMember::where('review_id', $review->id)
            ->where('user_id', $this->reviewer->id)
            ->first();
        $this->assertNotNull($member->accepted_at);
    }

    /**
     * Test removing a member
     */
    public function test_creator_can_remove_member(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);
        $member = ReviewMember::factory()->create([
            'review_id' => $review->id,
            'user_id' => $this->reviewer->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/reviews/{$review->id}/members/{$member->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('review_members', ['id' => $member->id]);
    }

    /**
     * Test updating member role
     */
    public function test_creator_can_update_member_role(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);
        $member = ReviewMember::factory()->create([
            'review_id' => $review->id,
            'user_id' => $this->reviewer->id,
            'role' => 'reviewer',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/reviews/{$review->id}/members/{$member->id}/role", [
                'role' => 'coordinator',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('review_members', [
            'id' => $member->id,
            'role' => 'coordinator',
        ]);
    }

    /**
     * Test member can access review
     */
    public function test_member_can_access_review(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);
        ReviewMember::factory()->create([
            'review_id' => $review->id,
            'user_id' => $this->reviewer->id,
        ]);

        $response = $this->actingAs($this->reviewer)
            ->getJson("/api/reviews/{$review->id}");

        $response->assertStatus(200);
    }

    /**
     * Test non-member cannot access review
     */
    public function test_non_member_cannot_access_review(): void
    {
        $review = Review::factory()->create(['user_id' => $this->user->id]);
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->getJson("/api/reviews/{$review->id}");

        $response->assertStatus(403);
    }
}
