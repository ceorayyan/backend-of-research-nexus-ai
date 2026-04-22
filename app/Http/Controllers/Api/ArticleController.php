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

        $articles = $review->articles()->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($articles);
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
