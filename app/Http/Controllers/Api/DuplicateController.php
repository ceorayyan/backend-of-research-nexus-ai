<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Duplicate;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuplicateController extends Controller
{
    /**
     * Detect duplicates for a review.
     *
     * @param Review $review
     * @param Request $request
     * @return JsonResponse
     */
    public function detect(Review $review, Request $request): JsonResponse
    {
        // Check authorization
        if ($review->user_id !== auth()->id() && !$review->hasMember(auth()->user())) {
            return response()->json([
                'message' => 'You do not have permission to access this review'
            ], 403);
        }

        // Validate request parameters
        $validated = $request->validate([
            'clearExisting' => 'boolean',
            'incrementalOnly' => 'boolean',
        ]);

        $clearExisting = $validated['clearExisting'] ?? false;
        $incrementalOnly = $validated['incrementalOnly'] ?? false;

        // Set execution time limit to 25 seconds
        set_time_limit(25);
        $startTime = microtime(true);
        $partial = false;

        try {
            // Check if duplicates already exist
            $existingDuplicatesCount = $review->duplicates()->count();
            
            // If clearExisting is true, delete all existing duplicates
            if ($clearExisting && $existingDuplicatesCount > 0) {
                $review->duplicates()->delete();
                Log::info('Cleared existing duplicates', [
                    'review_id' => $review->id,
                    'cleared_count' => $existingDuplicatesCount,
                ]);
            }

            // Get articles for detection
            if ($incrementalOnly && $review->last_detection_run) {
                // Only check articles added after last detection run
                $articles = $review->articles()
                    ->where('created_at', '>', $review->last_detection_run)
                    ->get();
                
                if ($articles->isEmpty()) {
                    return response()->json([
                        'message' => 'No new articles found since last detection run',
                        'data' => [
                            'duplicates' => [],
                            'total_duplicates' => 0,
                            'execution_time' => '0.00s',
                            'articles_checked' => 0,
                            'partial' => false,
                            'incremental' => true,
                        ]
                    ]);
                }
            } else {
                // Get all articles for the review
                $articles = $review->articles()->get();
            }
            
            $articlesChecked = $articles->count();

            if ($articlesChecked === 0) {
                return response()->json([
                    'message' => 'No articles found in this review',
                    'data' => [
                        'duplicates' => [],
                        'total_duplicates' => 0,
                        'execution_time' => '0.00s',
                        'articles_checked' => 0,
                        'partial' => false,
                        'incremental' => $incrementalOnly,
                    ]
                ]);
            }

            $duplicatePairs = [];

            if ($incrementalOnly && $review->last_detection_run) {
                // For incremental mode, compare new articles against all existing articles
                $allArticles = $review->articles()->get();
                $duplicatePairs = $this->findIncrementalDuplicates($articles, $allArticles, $review->id);
            } else {
                // Full detection mode
                // Step 1: URL/DOI Matching
                $urlGroups = $this->groupByUrl($articles);
                foreach ($urlGroups as $url => $groupArticles) {
                    if (count($groupArticles) >= 2) {
                        $pairs = $this->createPairsFromGroup($groupArticles, 100, 'Same DOI/URL', $review->id);
                        $duplicatePairs = array_merge($duplicatePairs, $pairs);
                    }
                }

                // Step 2: Exact Title Matching
                // Limit to first 10,000 articles for performance
                $titleMatchPairs = $this->findExactTitleMatches($review->id, min($articlesChecked, 10000));
                $duplicatePairs = array_merge($duplicatePairs, $titleMatchPairs);
            }

            // Remove duplicates from the pairs array (same article1_id and article2_id)
            $duplicatePairs = $this->deduplicatePairs($duplicatePairs);

            // Bulk insert duplicate pairs using database transaction
            DB::beginTransaction();
            try {
                // Process in batches of 500
                $batches = array_chunk($duplicatePairs, 500);
                foreach ($batches as $batch) {
                    Duplicate::insert($batch);
                }
                
                // Update last_detection_run timestamp
                $review->last_detection_run = now();
                $review->save();
                
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

            $executionTime = microtime(true) - $startTime;

            // Check if we hit the time limit
            if ($executionTime >= 24) {
                $partial = true;
            }

            // Log detection run
            Log::info('Duplicate detection completed', [
                'review_id' => $review->id,
                'articles_checked' => $articlesChecked,
                'duplicates_found' => count($duplicatePairs),
                'execution_time' => $executionTime,
                'partial' => $partial,
                'incremental' => $incrementalOnly,
                'cleared_existing' => $clearExisting,
            ]);

            return response()->json([
                'message' => $partial 
                    ? 'Duplicate detection completed with partial results due to timeout' 
                    : 'Duplicate detection completed successfully',
                'data' => [
                    'duplicates' => [],
                    'total_duplicates' => count($duplicatePairs),
                    'execution_time' => number_format($executionTime, 2) . 's',
                    'articles_checked' => $articlesChecked,
                    'partial' => $partial,
                    'incremental' => $incrementalOnly,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Duplicate detection failed', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Duplicate detection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Find duplicates for new articles against all existing articles (incremental mode).
     *
     * @param \Illuminate\Support\Collection $newArticles
     * @param \Illuminate\Support\Collection $allArticles
     * @param int $reviewId
     * @return array
     */
    private function findIncrementalDuplicates($newArticles, $allArticles, int $reviewId): array
    {
        $pairs = [];

        foreach ($newArticles as $newArticle) {
            foreach ($allArticles as $existingArticle) {
                // Skip comparing article with itself
                if ($newArticle->id === $existingArticle->id) {
                    continue;
                }

                $isDuplicate = false;
                $reason = '';
                $score = 0;

                // Check URL/DOI matching
                if (!empty($newArticle->url) && !empty($existingArticle->url)) {
                    $normalizedNewUrl = $this->normalizeUrl($newArticle->url);
                    $normalizedExistingUrl = $this->normalizeUrl($existingArticle->url);
                    
                    if ($normalizedNewUrl === $normalizedExistingUrl) {
                        $isDuplicate = true;
                        $reason = 'Same DOI/URL';
                        $score = 100;
                    }
                }

                // Check exact title matching if not already a duplicate
                if (!$isDuplicate && !empty($newArticle->title) && !empty($existingArticle->title)) {
                    $normalizedNewTitle = $this->normalizeTitle($newArticle->title);
                    $normalizedExistingTitle = $this->normalizeTitle($existingArticle->title);
                    
                    if ($normalizedNewTitle === $normalizedExistingTitle) {
                        $isDuplicate = true;
                        $reason = 'Exact title match';
                        $score = 100;
                    }
                }

                // If duplicate found, create pair
                if ($isDuplicate) {
                    $article1Id = min($newArticle->id, $existingArticle->id);
                    $article2Id = max($newArticle->id, $existingArticle->id);

                    $pairs[] = [
                        'review_id' => $reviewId,
                        'article1_id' => $article1Id,
                        'article2_id' => $article2Id,
                        'similarity_score' => $score,
                        'detection_reason' => $reason,
                        'status' => 'unresolved',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        return $pairs;
    }

    /**
     * Group articles by normalized URL.
     *
     * @param \Illuminate\Support\Collection $articles
     * @return array
     */
    private function groupByUrl($articles): array
    {
        $groups = [];

        foreach ($articles as $article) {
            if (!empty($article->url)) {
                $normalizedUrl = $this->normalizeUrl($article->url);
                if (!isset($groups[$normalizedUrl])) {
                    $groups[$normalizedUrl] = [];
                }
                $groups[$normalizedUrl][] = $article;
            }
        }

        return $groups;
    }

    /**
     * Normalize URL by removing protocol, www, and trailing slashes.
     *
     * @param string $url
     * @return string
     */
    private function normalizeUrl(string $url): string
    {
        $url = strtolower(trim($url));
        $url = preg_replace('#^https?://(www\.)?#', '', $url);
        $url = rtrim($url, '/');
        return $url;
    }

    /**
     * Normalize title by lowercasing, trimming, and removing extra whitespace.
     *
     * @param string $title
     * @return string
     */
    private function normalizeTitle(string $title): string
    {
        $title = strtolower(trim($title));
        $title = preg_replace('/\s+/', ' ', $title);
        return $title;
    }

    /**
     * Create duplicate pairs from a group of articles.
     *
     * @param array $articles
     * @param int $similarityScore
     * @param string $detectionReason
     * @param int $reviewId
     * @return array
     */
    private function createPairsFromGroup(array $articles, int $similarityScore, string $detectionReason, int $reviewId): array
    {
        $pairs = [];
        $count = count($articles);

        for ($i = 0; $i < $count - 1; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $article1Id = $articles[$i]->id;
                $article2Id = $articles[$j]->id;

                // Ensure article1_id < article2_id
                if ($article1Id > $article2Id) {
                    [$article1Id, $article2Id] = [$article2Id, $article1Id];
                }

                $pairs[] = [
                    'review_id' => $reviewId,
                    'article1_id' => $article1Id,
                    'article2_id' => $article2Id,
                    'similarity_score' => $similarityScore,
                    'detection_reason' => $detectionReason,
                    'status' => 'unresolved',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        return $pairs;
    }

    /**
     * Find exact title matches using SQL query.
     *
     * @param int $reviewId
     * @param int $limit
     * @return array
     */
    private function findExactTitleMatches(int $reviewId, int $limit): array
    {
        $pairs = [];

        // Get articles with normalized titles
        $articles = Article::where('review_id', $reviewId)
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->limit($limit)
            ->get();

        // Group by normalized title
        $titleGroups = [];
        foreach ($articles as $article) {
            $normalizedTitle = $this->normalizeTitle($article->title);
            if (!isset($titleGroups[$normalizedTitle])) {
                $titleGroups[$normalizedTitle] = [];
            }
            $titleGroups[$normalizedTitle][] = $article;
        }

        // Create pairs for groups with 2+ articles
        foreach ($titleGroups as $title => $groupArticles) {
            if (count($groupArticles) >= 2) {
                $groupPairs = $this->createPairsFromGroup($groupArticles, 100, 'Exact title match', $reviewId);
                $pairs = array_merge($pairs, $groupPairs);
            }
        }

        return $pairs;
    }

    /**
     * Remove duplicate pairs (same article1_id and article2_id).
     *
     * @param array $pairs
     * @return array
     */
    private function deduplicatePairs(array $pairs): array
    {
        $unique = [];
        $seen = [];

        foreach ($pairs as $pair) {
            $key = $pair['article1_id'] . '-' . $pair['article2_id'];
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $unique[] = $pair;
            }
        }

        return $unique;
    }

    /**
     * List duplicates for a review with pagination.
     *
     * @param Review $review
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Review $review, Request $request): JsonResponse
    {
        // Check authorization
        if ($review->user_id !== auth()->id() && !$review->hasMember(auth()->user())) {
            return response()->json([
                'message' => 'You do not have permission to access this review'
            ], 403);
        }

        // Validate pagination parameters
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'status' => 'string|in:unresolved,deleted,not_duplicate,resolved',
        ]);

        $page = $validated['page'] ?? 1;
        $perPage = $validated['per_page'] ?? 20;
        $status = $validated['status'] ?? null;

        // Build query
        $query = Duplicate::where('review_id', $review->id)
            ->with([
                'article1:id,reference_id,title,authors,created_at,labels,screening_notes,screening_decision_by',
                'article2:id,reference_id,title,authors,created_at,labels,screening_notes,screening_decision_by'
            ]);

        // Apply status filter
        if ($status === 'deleted') {
            // "Deleted" tab = resolved pairs where a loser was excluded
            $query->where('status', 'resolved')->whereNotNull('kept_article_id');
        } elseif ($status) {
            $query->where('status', $status);
        }

        // Order by created_at descending (newest first)
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $duplicates = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $duplicates->items(),
            'current_page' => $duplicates->currentPage(),
            'last_page' => $duplicates->lastPage(),
            'per_page' => $duplicates->perPage(),
            'total' => $duplicates->total(),
            'from' => $duplicates->firstItem(),
            'to' => $duplicates->lastItem(),
        ]);
    }

    /**
     * Get duplicate counts grouped by status.
     * "deleted" = resolved pairs where a loser article was excluded (kept_article_id IS NOT NULL)
     * "resolved" = resolved pairs where both articles were kept (kept_article_id IS NULL) — not used currently
     *
     * @param Review $review
     * @return JsonResponse
     */
    public function getCounts(Review $review): JsonResponse
    {
        // Check authorization
        if ($review->user_id !== auth()->id() && !$review->hasMember(auth()->user())) {
            return response()->json([
                'message' => 'You do not have permission to access this review'
            ], 403);
        }

        $base = Duplicate::where('review_id', $review->id);

        $unresolvedCount    = (clone $base)->where('status', 'unresolved')->count();
        $notDuplicateCount  = (clone $base)->where('status', 'not_duplicate')->count();
        $resolvedCount      = (clone $base)->where('status', 'resolved')->whereNotNull('kept_article_id')->count();
        // "deleted" mirrors resolved-with-loser (same pairs, loser perspective)
        $deletedCount       = $resolvedCount;

        $statusCounts = [
            'unresolved'    => $unresolvedCount,
            'not_duplicate' => $notDuplicateCount,
            'resolved'      => $resolvedCount,
            'deleted'       => $deletedCount,
            'total'         => $unresolvedCount + $notDuplicateCount + $resolvedCount,
        ];

        return response()->json($statusCounts);
    }

    /**
     * Resolve a duplicate pair by keeping one article.
     * - keep=left  → pair status = 'resolved', kept_article_id = article1_id, article2 soft-excluded
     * - keep=right → pair status = 'resolved', kept_article_id = article2_id, article1 soft-excluded
     * - keep=both  → pair status = 'not_duplicate', no article changes
     *
     * The "Deleted" tab shows resolved pairs where kept_article_id IS NOT NULL.
     */
    public function resolve(Duplicate $duplicate, Request $request): JsonResponse
    {
        $review = $duplicate->review;
        if ($review->user_id !== auth()->id() && !$review->hasMember(auth()->user())) {
            return response()->json(['message' => 'You do not have permission to access this review'], 403);
        }

        $validated = $request->validate([
            'keep' => 'required|string|in:left,right,both',
        ]);

        $keep = $validated['keep'];

        DB::beginTransaction();
        try {
            if ($keep === 'both') {
                $duplicate->status = 'not_duplicate';
                $duplicate->marked_by_user_id = auth()->id();
                $duplicate->kept_article_id = null;
                $duplicate->save();
            } else {
                $winnerArticleId = $keep === 'left' ? $duplicate->article1_id : $duplicate->article2_id;
                $loserArticleId  = $keep === 'left' ? $duplicate->article2_id : $duplicate->article1_id;

                $duplicate->status = 'resolved';
                $duplicate->marked_by_user_id = auth()->id();
                $duplicate->kept_article_id = $winnerArticleId;
                $duplicate->save();

                // Soft-exclude the loser article from screening
                Article::where('id', $loserArticleId)->update(['status' => 'excluded']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to resolve duplicate', [
                'duplicate_id' => $duplicate->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Failed to resolve duplicate: ' . $e->getMessage()], 500);
        }

        $duplicate->load([
            'article1:id,reference_id,title,authors,created_at,labels,screening_notes,screening_decision_by',
            'article2:id,reference_id,title,authors,created_at,labels,screening_notes,screening_decision_by',
        ]);

        return response()->json([
            'message' => 'Duplicate resolved successfully',
            'data'    => $duplicate,
        ]);
    }

    /**
     * Update the status of a duplicate pair.
     *
     * @param Duplicate $duplicate
     * @param Request $request
     * @return JsonResponse
     */
    public function updateStatus(Duplicate $duplicate, Request $request): JsonResponse
    {
        // Check authorization - user must own the review or be a member
        $review = $duplicate->review;
        if ($review->user_id !== auth()->id() && !$review->hasMember(auth()->user())) {
            return response()->json([
                'message' => 'You do not have permission to access this review'
            ], 403);
        }

        // Validate status parameter
        $validated = $request->validate([
            'status' => 'required|string|in:unresolved,deleted,not_duplicate,resolved',
        ]);

        // Update duplicate record
        $duplicate->status = $validated['status'];
        $duplicate->marked_by_user_id = auth()->id();
        $duplicate->save();

        // Load article relationships for response
        $duplicate->load([
            'article1:id,reference_id,title,authors,created_at,labels,screening_notes,screening_decision_by',
            'article2:id,reference_id,title,authors,created_at,labels,screening_notes,screening_decision_by'
        ]);

        return response()->json([
            'message' => 'Duplicate status updated successfully',
            'data' => $duplicate,
        ]);
    }

    /**
     * Mark a duplicate pair as "not duplicate".
     *
     * @param Duplicate $duplicate
     * @return JsonResponse
     */
    public function markNotDuplicate(Duplicate $duplicate): JsonResponse
    {
        // Check authorization - user must own the review or be a member
        $review = $duplicate->review;
        if ($review->user_id !== auth()->id() && !$review->hasMember(auth()->user())) {
            return response()->json([
                'message' => 'You do not have permission to access this review'
            ], 403);
        }

        // Update status to "not_duplicate"
        $duplicate->status = 'not_duplicate';
        $duplicate->marked_by_user_id = auth()->id();
        $duplicate->save();

        return response()->json([
            'message' => 'Duplicate pair marked as not duplicate',
            'data' => $duplicate,
        ]);
    }

    /**
     * Delete a duplicate pair.
     *
     * @param Duplicate $duplicate
     * @return JsonResponse
     */
    public function destroy(Duplicate $duplicate): JsonResponse
    {
        // Check authorization - user must own the review or be a member
        $review = $duplicate->review;
        if ($review->user_id !== auth()->id() && !$review->hasMember(auth()->user())) {
            return response()->json([
                'message' => 'You do not have permission to access this review'
            ], 403);
        }

        // Delete the duplicate pair
        $duplicate->delete();

        return response()->json([
            'message' => 'Duplicate pair deleted successfully',
        ]);
    }
}
