<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Review;
use App\Models\Article;
use App\Models\Duplicate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Review $review;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->review = Review::factory()->create(['user_id' => $this->user->id]);
    }

    /**
     * Test duplicate detection with no articles
     */
    public function test_detection_with_no_articles(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$this->review->id}/duplicates/detect");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'No articles found in this review',
                'data' => [
                    'total_duplicates' => 0,
                    'articles_checked' => 0,
                    'partial' => false,
                ],
            ]);
    }

    /**
     * Test duplicate detection with URL matching
     */
    public function test_detection_with_url_matching(): void
    {
        // Create articles with same URL
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Article 1',
            'url' => 'https://example.com/article',
        ]);
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Article 2',
            'url' => 'http://www.example.com/article/',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$this->review->id}/duplicates/detect");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'total_duplicates',
                    'execution_time',
                    'articles_checked',
                    'partial',
                ],
            ]);

        // Verify duplicate was created
        $this->assertDatabaseHas('duplicates', [
            'review_id' => $this->review->id,
            'similarity_score' => 100,
            'detection_reason' => 'Same DOI/URL',
            'status' => 'unresolved',
        ]);

        $this->assertEquals(1, Duplicate::where('review_id', $this->review->id)->count());
    }

    /**
     * Test duplicate detection with exact title matching
     */
    public function test_detection_with_exact_title_matching(): void
    {
        // Create articles with same title
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'The Impact of Climate Change',
            'url' => 'https://example.com/article1',
        ]);
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => '  THE IMPACT OF CLIMATE CHANGE  ',
            'url' => 'https://example.com/article2',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$this->review->id}/duplicates/detect");

        $response->assertStatus(200);

        // Verify duplicate was created
        $this->assertDatabaseHas('duplicates', [
            'review_id' => $this->review->id,
            'similarity_score' => 100,
            'detection_reason' => 'Exact title match',
            'status' => 'unresolved',
        ]);
    }

    /**
     * Test duplicate detection ensures article1_id < article2_id
     */
    public function test_detection_ensures_article_order(): void
    {
        $article1 = Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Test Article',
            'url' => 'https://example.com/article',
        ]);
        $article2 = Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Test Article',
            'url' => 'https://example.com/article',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$this->review->id}/duplicates/detect");

        $response->assertStatus(200);

        $duplicate = Duplicate::where('review_id', $this->review->id)->first();
        $this->assertNotNull($duplicate);
        $this->assertTrue($duplicate->article1_id < $duplicate->article2_id);
    }

    /**
     * Test duplicate detection with multiple duplicates
     */
    public function test_detection_with_multiple_duplicates(): void
    {
        // Create 3 articles with same URL
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Article 1',
            'url' => 'https://example.com/article',
        ]);
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Article 2',
            'url' => 'https://example.com/article',
        ]);
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Article 3',
            'url' => 'https://example.com/article',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$this->review->id}/duplicates/detect");

        $response->assertStatus(200);

        // Should create 3 pairs: (1,2), (1,3), (2,3)
        $this->assertEquals(3, Duplicate::where('review_id', $this->review->id)->count());
    }

    /**
     * Test duplicate detection removes duplicate pairs
     */
    public function test_detection_removes_duplicate_pairs(): void
    {
        // Create articles that match both by URL and title
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Same Title',
            'url' => 'https://example.com/article',
        ]);
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Same Title',
            'url' => 'https://example.com/article',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$this->review->id}/duplicates/detect");

        $response->assertStatus(200);

        // Should only create 1 pair, not 2 (one from URL, one from title)
        $this->assertEquals(1, Duplicate::where('review_id', $this->review->id)->count());
    }

    /**
     * Test unauthorized user cannot detect duplicates
     */
    public function test_unauthorized_user_cannot_detect_duplicates(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->postJson("/api/reviews/{$this->review->id}/duplicates/detect");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You do not have permission to access this review',
            ]);
    }

    /**
     * Test detection with articles without URLs
     */
    public function test_detection_with_articles_without_urls(): void
    {
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Article 1',
            'url' => null,
        ]);
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Article 2',
            'url' => null,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$this->review->id}/duplicates/detect");

        $response->assertStatus(200);

        // Should not create any duplicates based on URL
        $this->assertEquals(0, Duplicate::where('review_id', $this->review->id)->count());
    }

    /**
     * Test detection with articles with empty titles
     */
    public function test_detection_with_empty_titles(): void
    {
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => '',
            'url' => 'https://example.com/article1',
        ]);
        Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => '',
            'url' => 'https://example.com/article2',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/reviews/{$this->review->id}/duplicates/detect");

        $response->assertStatus(200);

        // Should not create duplicates based on empty titles
        $this->assertEquals(0, Duplicate::where('review_id', $this->review->id)->count());
    }

    /**
     * Test listing duplicates with pagination
     */
    public function test_list_duplicates_with_pagination(): void
    {
        // Create some duplicates
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);
        $article3 = Article::factory()->create(['review_id' => $this->review->id]);

        Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article3->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'resolved',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/reviews/{$this->review->id}/duplicates?page=1&per_page=20");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
                'from',
                'to',
            ])
            ->assertJson([
                'current_page' => 1,
                'per_page' => 20,
                'total' => 2,
            ]);

        $this->assertCount(2, $response->json('data'));
    }

    /**
     * Test listing duplicates with status filter
     */
    public function test_list_duplicates_with_status_filter(): void
    {
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);
        $article3 = Article::factory()->create(['review_id' => $this->review->id]);

        Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article3->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'resolved',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/reviews/{$this->review->id}/duplicates?status=unresolved");

        $response->assertStatus(200)
            ->assertJson([
                'total' => 1,
            ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('unresolved', $response->json('data.0.status'));
    }

    /**
     * Test listing duplicates eager loads article relationships
     */
    public function test_list_duplicates_eager_loads_articles(): void
    {
        $article1 = Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Article 1',
            'authors' => 'Author 1',
        ]);
        $article2 = Article::factory()->create([
            'review_id' => $this->review->id,
            'title' => 'Article 2',
            'authors' => 'Author 2',
        ]);

        Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/reviews/{$this->review->id}/duplicates");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'article1' => ['id', 'title', 'authors', 'created_at'],
                        'article2' => ['id', 'title', 'authors', 'created_at'],
                    ],
                ],
            ]);
    }

    /**
     * Test unauthorized user cannot list duplicates
     */
    public function test_unauthorized_user_cannot_list_duplicates(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->getJson("/api/reviews/{$this->review->id}/duplicates");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You do not have permission to access this review',
            ]);
    }

    /**
     * Test get duplicate counts
     */
    public function test_get_duplicate_counts(): void
    {
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);
        $article3 = Article::factory()->create(['review_id' => $this->review->id]);
        $article4 = Article::factory()->create(['review_id' => $this->review->id]);

        // Create duplicates with different statuses
        Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article3->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article2->id,
            'article2_id' => $article3->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'resolved',
        ]);

        Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article2->id,
            'article2_id' => $article4->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'not_duplicate',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/reviews/{$this->review->id}/duplicates/counts");

        $response->assertStatus(200)
            ->assertJson([
                'unresolved' => 2,
                'deleted' => 0,
                'not_duplicate' => 1,
                'resolved' => 1,
                'total' => 4,
            ]);
    }

    /**
     * Test get duplicate counts with no duplicates
     */
    public function test_get_duplicate_counts_with_no_duplicates(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson("/api/reviews/{$this->review->id}/duplicates/counts");

        $response->assertStatus(200)
            ->assertJson([
                'unresolved' => 0,
                'deleted' => 0,
                'not_duplicate' => 0,
                'resolved' => 0,
                'total' => 0,
            ]);
    }

    /**
     * Test unauthorized user cannot get duplicate counts
     */
    public function test_unauthorized_user_cannot_get_counts(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->getJson("/api/reviews/{$this->review->id}/duplicates/counts");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You do not have permission to access this review',
            ]);
    }

    /**
     * Test update duplicate status
     */
    public function test_update_duplicate_status(): void
    {
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);

        $duplicate = Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/duplicates/{$duplicate->id}/status", [
                'status' => 'resolved',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Duplicate status updated successfully',
                'data' => [
                    'id' => $duplicate->id,
                    'status' => 'resolved',
                    'marked_by_user_id' => $this->user->id,
                ],
            ])
            ->assertJsonStructure([
                'data' => [
                    'article1' => ['id', 'title', 'authors', 'created_at'],
                    'article2' => ['id', 'title', 'authors', 'created_at'],
                ],
            ]);

        $this->assertDatabaseHas('duplicates', [
            'id' => $duplicate->id,
            'status' => 'resolved',
            'marked_by_user_id' => $this->user->id,
        ]);
    }

    /**
     * Test update duplicate status with invalid status
     */
    public function test_update_duplicate_status_with_invalid_status(): void
    {
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);

        $duplicate = Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/duplicates/{$duplicate->id}/status", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test update duplicate status without status parameter
     */
    public function test_update_duplicate_status_without_status_parameter(): void
    {
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);

        $duplicate = Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/duplicates/{$duplicate->id}/status", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /**
     * Test unauthorized user cannot update duplicate status
     */
    public function test_unauthorized_user_cannot_update_duplicate_status(): void
    {
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);

        $duplicate = Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->putJson("/api/duplicates/{$duplicate->id}/status", [
                'status' => 'resolved',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You do not have permission to access this review',
            ]);
    }

    /**
     * Test mark duplicate as not duplicate
     */
    public function test_mark_duplicate_as_not_duplicate(): void
    {
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);

        $duplicate = Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/duplicates/{$duplicate->id}/not-duplicate");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Duplicate pair marked as not duplicate',
                'data' => [
                    'id' => $duplicate->id,
                    'status' => 'not_duplicate',
                    'marked_by_user_id' => $this->user->id,
                ],
            ]);

        $this->assertDatabaseHas('duplicates', [
            'id' => $duplicate->id,
            'status' => 'not_duplicate',
            'marked_by_user_id' => $this->user->id,
        ]);
    }

    /**
     * Test unauthorized user cannot mark duplicate as not duplicate
     */
    public function test_unauthorized_user_cannot_mark_not_duplicate(): void
    {
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);

        $duplicate = Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->postJson("/api/duplicates/{$duplicate->id}/not-duplicate");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You do not have permission to access this review',
            ]);
    }

    /**
     * Test delete duplicate pair
     */
    public function test_delete_duplicate_pair(): void
    {
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);

        $duplicate = Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/duplicates/{$duplicate->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Duplicate pair deleted successfully',
            ]);

        $this->assertDatabaseMissing('duplicates', [
            'id' => $duplicate->id,
        ]);
    }

    /**
     * Test unauthorized user cannot delete duplicate pair
     */
    public function test_unauthorized_user_cannot_delete_duplicate(): void
    {
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);

        $duplicate = Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->deleteJson("/api/duplicates/{$duplicate->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You do not have permission to access this review',
            ]);

        // Verify duplicate still exists
        $this->assertDatabaseHas('duplicates', [
            'id' => $duplicate->id,
        ]);
    }

    /**
     * Test update status updates timestamp
     */
    public function test_update_status_updates_timestamp(): void
    {
        $article1 = Article::factory()->create(['review_id' => $this->review->id]);
        $article2 = Article::factory()->create(['review_id' => $this->review->id]);

        $duplicate = Duplicate::create([
            'review_id' => $this->review->id,
            'article1_id' => $article1->id,
            'article2_id' => $article2->id,
            'similarity_score' => 100,
            'detection_reason' => 'Test',
            'status' => 'unresolved',
        ]);

        $originalUpdatedAt = $duplicate->updated_at;

        // Wait a moment to ensure timestamp changes
        sleep(1);

        $response = $this->actingAs($this->user)
            ->putJson("/api/duplicates/{$duplicate->id}/status", [
                'status' => 'resolved',
            ]);

        $response->assertStatus(200);

        $duplicate->refresh();
        $this->assertNotEquals($originalUpdatedAt, $duplicate->updated_at);
    }
}
