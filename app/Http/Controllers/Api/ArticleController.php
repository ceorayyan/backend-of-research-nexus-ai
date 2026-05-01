<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    /**
     * Upload an article to a review.
     */
    public function store(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has access to this review
        if ($review->user_id !== $user->id && !$review->hasMember($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'authors' => 'nullable|string',
            'abstract' => 'nullable|string',
            'url' => 'nullable|url',
            'file' => 'nullable|file|mimes:pdf,doc,docx,csv,ris,bib,xml,nbib|max:10240',
        ]);

        // If no title provided, use filename
        $title = $validated['title'];
        if (!$title && $request->hasFile('file')) {
            $title = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);
        }

        if (!$title) {
            return response()->json(['message' => 'Title is required'], 422);
        }

        // Handle file upload
        $filePath = null;
        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');
                $path = $file->store('articles', 'public');
                $filePath = $path;

                // If CSV file, parse and create multiple articles
                if ($file->getClientOriginalExtension() === 'csv') {
                    return $this->importFromCSV($review, $file, $filePath);
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to upload file: ' . $e->getMessage()], 422);
            }
        }

        // Create single article
        $data = [
            'title' => $title,
            'authors' => $validated['authors'] ?? null,
            'abstract' => $validated['abstract'] ?? null,
            'url' => $validated['url'] ?? null,
            'file_path' => $filePath,
        ];

        try {
            $article = $review->articles()->create($data);

            return response()->json([
                'message' => 'Article uploaded successfully',
                'data' => $article,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create article: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Import articles from CSV file.
     */
    private function importFromCSV(Review $review, $file, string $filePath): JsonResponse
    {
        try {
            $csvData = array_map('str_getcsv', file($file->getRealPath()));
            $headers = array_map('trim', $csvData[0]);
            $articlesCreated = 0;
            $errors = [];
            $batchSize = 500; // Insert 500 articles at a time
            $articlesBatch = [];

            // Find column indices
            $titleIndex = $this->findColumnIndex($headers, ['title', 'Title']);
            $authorsIndex = $this->findColumnIndex($headers, ['authors', 'Authors', 'Author']);
            $abstractIndex = $this->findColumnIndex($headers, ['abstract', 'Abstract']);
            $urlIndex = $this->findColumnIndex($headers, ['url', 'URL', 'doi', 'DOI']);

            $now = now();

            // Process each row (skip header)
            for ($i = 1; $i < count($csvData); $i++) {
                $row = $csvData[$i];
                
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }

                try {
                    $articleData = [
                        'review_id' => $review->id,
                        'title' => $titleIndex !== false && isset($row[$titleIndex]) ? trim($row[$titleIndex]) : "Article $i",
                        'authors' => $authorsIndex !== false && isset($row[$authorsIndex]) ? trim($row[$authorsIndex]) : null,
                        'abstract' => $abstractIndex !== false && isset($row[$abstractIndex]) ? trim($row[$abstractIndex]) : null,
                        'url' => $urlIndex !== false && isset($row[$urlIndex]) ? trim($row[$urlIndex]) : null,
                        'file_path' => $filePath,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];

                    $articlesBatch[] = $articleData;
                    $articlesCreated++;

                    // Insert batch when it reaches the batch size
                    if (count($articlesBatch) >= $batchSize) {
                        \DB::table('articles')->insert($articlesBatch);
                        $articlesBatch = [];
                    }
                } catch (\Exception $e) {
                    $errors[] = "Row $i: " . $e->getMessage();
                }
            }

            // Insert remaining articles
            if (!empty($articlesBatch)) {
                \DB::table('articles')->insert($articlesBatch);
            }

            return response()->json([
                'message' => "CSV imported successfully. Created $articlesCreated articles.",
                'data' => [
                    'articles_created' => $articlesCreated,
                    'errors' => $errors,
                    'file_path' => $filePath,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to parse CSV: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Find column index by possible names.
     */
    private function findColumnIndex(array $headers, array $possibleNames): int|false
    {
        foreach ($possibleNames as $name) {
            $index = array_search($name, $headers, true);
            if ($index !== false) {
                return $index;
            }
        }
        return false;
    }

    /**
     * Get articles for a review.
     */
    public function index(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has access to this review
        if ($review->user_id !== $user->id && !$review->hasMember($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Get per_page from request, default to 100
        $perPage = $request->input('per_page', 100);
        $perPage = min(max($perPage, 10), 100); // Between 10 and 100

        // Only select necessary columns for better performance
        $articles = $review->articles()
            ->select('id', 'reference_id', 'review_id', 'title', 'authors', 'url', 'abstract', 'journal', 'year', 'keywords', 'labels', 'exclusion_reasons', 'status', 'screening_decision_by', 'screening_notes', 'fulltext_status', 'fulltext_decision_by', 'fulltext_notes', 'fulltext_labels', 'fulltext_exclusion_reasons', 'file_path', 'fulltext_pdf_path', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($articles);
    }

    /**
     * Get screening decision statistics for a review.
     */
    public function getScreeningStats(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has access to this review
        if ($review->user_id !== $user->id && !$review->hasMember($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $total = $review->articles()->count();
        
        // Screening statistics
        $included = $review->articles()->where('status', 'included')->count();
        $excluded = $review->articles()->where('status', 'excluded')->count();
        $maybe = $review->articles()->where('status', 'undecided')->whereNotNull('screening_decision_by')->count();
        $screened = $included + $excluded + $maybe;
        $unscreened = $total - $screened;
        
        // Full-text statistics
        $fulltext_total = $review->articles()->whereNotNull('fulltext_status')->where('fulltext_status', '!=', 'none')->count();
        $fulltext_included = $review->articles()->where('fulltext_status', 'included')->count();
        $fulltext_excluded = $review->articles()->where('fulltext_status', 'excluded')->count();
        $fulltext_maybe = $review->articles()->where('fulltext_status', 'maybe')->count();

        return response()->json([
            'total' => $total,
            'screened' => $screened,
            'unscreened' => $unscreened,
            'included' => $included,
            'excluded' => $excluded,
            'maybe' => $maybe,
            'fulltext_total' => $fulltext_total,
            'fulltext_included' => $fulltext_included,
            'fulltext_excluded' => $fulltext_excluded,
            'fulltext_maybe' => $fulltext_maybe,
        ]);
    }

    /**
     * Update an article.
     */
    public function update(Article $article, Request $request): JsonResponse
    {
        $user = $request->user();
        $review = $article->review;

        // Check if user has access to this review
        if ($review->user_id !== $user->id && !$review->hasMember($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'authors' => 'nullable|string',
            'abstract' => 'nullable|string',
            'url' => 'nullable|url',
            'status' => 'nullable|in:included,excluded,undecided',
            'screening_notes' => 'nullable|string',
            'keywords' => 'nullable|array',
            'labels' => 'nullable|array',
            'labels.*' => 'string',
            'exclusion_reasons' => 'nullable|array',
            'exclusion_reasons.*' => 'string',
            'journal' => 'nullable|string',
            'year' => 'nullable|integer',
            'fulltext_status' => 'nullable|in:none,included,excluded,maybe',
            'fulltext_decision_by' => 'nullable|string',
            'fulltext_notes' => 'nullable|string',
            'fulltext_labels' => 'nullable|array',
            'fulltext_labels.*' => 'string',
            'fulltext_exclusion_reasons' => 'nullable|array',
            'fulltext_exclusion_reasons.*' => 'string',
            'file' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        // Handle PDF file upload
        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');
                $path = $file->store('articles/fulltext', 'public');
                
                // Delete old file if exists
                if ($article->fulltext_pdf_path) {
                    \Storage::disk('public')->delete($article->fulltext_pdf_path);
                }
                
                $validated['fulltext_pdf_path'] = $path;
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to upload file: ' . $e->getMessage()], 422);
            }
        }

        $article->update($validated);
        
        // Refresh to get the updated data
        $article->refresh();

        return response()->json([
            'message' => 'Article updated successfully',
            'data' => $article,
        ]);
    }

    /**
     * Update article screening decision.
     */
    public function updateScreening(Article $article, Request $request): JsonResponse
    {
        $user = $request->user();
        $review = $article->review;

        // Check if user has access to this review
        if ($review->user_id !== $user->id && !$review->hasMember($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'screening_decision' => 'required|in:included,excluded,undecided',
            'screening_decision_by' => 'nullable|string',
            'screening_notes' => 'nullable|string',
            'labels' => 'nullable|array',
            'labels.*' => 'string',
            'exclusion_reasons' => 'nullable|array',
            'exclusion_reasons.*' => 'string',
        ]);

        $article->update([
            'status' => $validated['screening_decision'],
            'screening_decision_by' => $validated['screening_decision_by'] ?? $article->screening_decision_by,
            'screening_notes' => $validated['screening_notes'] ?? $article->screening_notes,
            'labels' => $validated['labels'] ?? $article->labels,
            'exclusion_reasons' => $validated['exclusion_reasons'] ?? $article->exclusion_reasons,
        ]);

        return response()->json([
            'message' => 'Screening updated successfully',
            'data' => $article,
        ]);
    }

    /**
     * Bulk update articles.
     */
    public function bulkUpdate(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has access to this review
        if ($review->user_id !== $user->id && !$review->hasMember($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'article_ids' => 'required|array',
            'article_ids.*' => 'integer',
            'screening_decision' => 'nullable|in:include,exclude,maybe',
            'screening_notes' => 'nullable|string',
            'labels' => 'nullable|array',
            'labels.*' => 'string',
            'exclusion_reasons' => 'nullable|array',
            'exclusion_reasons.*' => 'string',
        ]);

        try {
            $updateData = [];
            if ($validated['screening_decision'] ?? null) {
                // Map UI values to stored status
                $map = ['include' => 'included', 'exclude' => 'excluded', 'maybe' => 'undecided'];
                $updateData['status'] = $map[$validated['screening_decision']] ?? 'undecided';
            }
            if ($validated['screening_notes'] ?? null) {
                $updateData['screening_notes'] = $validated['screening_notes'];
            }
            if (array_key_exists('labels', $validated)) {
                $updateData['labels'] = $validated['labels'];
            }
            if (array_key_exists('exclusion_reasons', $validated)) {
                $updateData['exclusion_reasons'] = $validated['exclusion_reasons'];
            }

            if (!empty($updateData)) {
                Article::whereIn('id', $validated['article_ids'])
                    ->where('review_id', $review->id)
                    ->update($updateData);
            }

            return response()->json([
                'message' => 'Articles updated successfully',
                'data' => [
                    'updated_count' => count($validated['article_ids']),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update articles: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Detect duplicate articles in a review.
     * Ultra-optimized for very large datasets (20k+ articles).
     */
    public function detectDuplicates(Review $review, Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user has access to this review
        if ($review->user_id !== $user->id && !$review->hasMember($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Set execution time limit to 30 seconds
        set_time_limit(30);
        
        $startTime = microtime(true);
        $maxExecutionTime = 25; // 25 seconds max
        $duplicates = [];

        // Get article count first
        $articleCount = $review->articles()->count();

        // For very large datasets (>15k), only do URL matching
        if ($articleCount > 15000) {
            // Step 1: Find exact URL matches ONLY (fastest)
            $urlGroups = $review->articles()
                ->whereNotNull('url')
                ->where('url', '!=', '')
                ->select('id', 'title', 'url')
                ->get()
                ->groupBy('url');

            foreach ($urlGroups as $url => $articles) {
                if ($articles->count() > 1) {
                    $articlesList = $articles->toArray();
                    for ($i = 0; $i < count($articlesList); $i++) {
                        for ($j = $i + 1; $j < count($articlesList); $j++) {
                            $duplicates[] = [
                                'article1_id' => $articlesList[$i]['id'],
                                'article2_id' => $articlesList[$j]['id'],
                                'article1_title' => $articlesList[$i]['title'],
                                'article2_title' => $articlesList[$j]['title'],
                                'similarity' => 100,
                                'reason' => 'Same DOI/URL',
                            ];
                        }
                    }
                }
            }

            $executionTime = round(microtime(true) - $startTime, 2);

            return response()->json([
                'message' => 'Duplicate detection completed (URL matches only - title matching skipped for very large dataset)',
                'data' => [
                    'duplicates' => $duplicates,
                    'total_duplicates' => count($duplicates),
                    'execution_time' => $executionTime . 's',
                    'articles_checked' => $articleCount,
                    'partial' => true,
                ],
            ]);
        }

        // For medium datasets (5k-15k), do URL + simplified title matching
        // Step 1: Find exact URL matches
        $urlGroups = $review->articles()
            ->whereNotNull('url')
            ->where('url', '!=', '')
            ->select('id', 'title', 'url')
            ->get()
            ->groupBy('url');

        foreach ($urlGroups as $url => $articles) {
            if ($articles->count() > 1) {
                $articlesList = $articles->toArray();
                for ($i = 0; $i < count($articlesList); $i++) {
                    for ($j = $i + 1; $j < count($articlesList); $j++) {
                        $duplicates[] = [
                            'article1_id' => $articlesList[$i]['id'],
                            'article2_id' => $articlesList[$j]['id'],
                            'article1_title' => $articlesList[$i]['title'],
                            'article2_title' => $articlesList[$j]['title'],
                            'similarity' => 100,
                            'reason' => 'Same DOI/URL',
                        ];
                    }
                }
            }
        }

        // Check timeout
        if ((microtime(true) - $startTime) > $maxExecutionTime) {
            return response()->json([
                'message' => 'Duplicate detection completed (URL matches only)',
                'data' => [
                    'duplicates' => $duplicates,
                    'total_duplicates' => count($duplicates),
                    'partial' => true,
                    'execution_time' => round(microtime(true) - $startTime, 2) . 's',
                    'articles_checked' => $articleCount,
                ],
            ]);
        }

        // Step 2: Find exact title matches using optimized query with LIMIT
        // Only check first 10,000 articles to avoid timeout
        $limitedArticles = min($articleCount, 10000);
        
        $exactTitleDuplicates = \DB::select("
            SELECT a1.id as article1_id, a2.id as article2_id, 
                   a1.title as article1_title, a2.title as article2_title
            FROM (
                SELECT id, title FROM articles 
                WHERE review_id = ? 
                ORDER BY id 
                LIMIT ?
            ) a1
            INNER JOIN (
                SELECT id, title FROM articles 
                WHERE review_id = ? 
                ORDER BY id 
                LIMIT ?
            ) a2 ON LOWER(TRIM(a1.title)) = LOWER(TRIM(a2.title))
            WHERE a1.id < a2.id
        ", [$review->id, $limitedArticles, $review->id, $limitedArticles]);

        $processed = [];
        foreach ($exactTitleDuplicates as $dup) {
            $duplicates[] = [
                'article1_id' => $dup->article1_id,
                'article2_id' => $dup->article2_id,
                'article1_title' => $dup->article1_title,
                'article2_title' => $dup->article2_title,
                'similarity' => 100,
                'reason' => 'Exact title match',
            ];
            $processed[$dup->article1_id . '-' . $dup->article2_id] = true;
        }

        $executionTime = round(microtime(true) - $startTime, 2);

        return response()->json([
            'message' => 'Duplicate detection completed',
            'data' => [
                'duplicates' => $duplicates,
                'total_duplicates' => count($duplicates),
                'execution_time' => $executionTime . 's',
                'articles_checked' => $articleCount,
                'partial' => $articleCount > 10000,
            ],
        ]);
    }

    /**
     * Create a fingerprint for a title (first 3 words + last 3 words).
     */
    private function createTitleFingerprint(string $title): string
    {
        $title = strtolower(trim($title));
        $title = preg_replace('/[^a-z0-9\s]/', '', $title);
        $words = array_filter(explode(' ', $title));
        
        if (count($words) <= 6) {
            return implode(' ', $words);
        }
        
        $first3 = array_slice($words, 0, 3);
        $last3 = array_slice($words, -3);
        
        return implode(' ', array_merge($first3, $last3));
    }

    /**
     * Quick similarity check using character overlap (faster than Levenshtein).
     */
    private function quickSimilarity(string $str1, string $str2): int
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        // If strings are identical, return 100
        if ($str1 === $str2) {
            return 100;
        }

        $len1 = strlen($str1);
        $len2 = strlen($str2);
        
        // If length difference is more than 30%, likely not duplicates
        if (abs($len1 - $len2) / max($len1, $len2) > 0.3) {
            return 0;
        }

        // Use similar_text for faster comparison
        similar_text($str1, $str2, $percent);
        
        return (int) round($percent);
    }

    /**
     * Delete an article.
     */
    public function destroy(Article $article, Request $request): JsonResponse
    {
        $user = $request->user();
        $review = $article->review;

        // Check if user has access to this review
        if ($review->user_id !== $user->id && !$review->isCoordinator($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete file if exists
        if ($article->file_path) {
            \Storage::disk('public')->delete($article->file_path);
        }

        $article->delete();

        return response()->json(['message' => 'Article deleted successfully']);
    }
}
