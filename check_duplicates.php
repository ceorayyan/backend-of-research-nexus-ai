<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Article;
use App\Models\Review;

echo "=== Checking for Duplicates ===\n\n";

// Get all reviews
$reviews = Review::all();
echo "Total reviews: " . $reviews->count() . "\n\n";

foreach ($reviews as $review) {
    echo "Review #{$review->id}: {$review->title}\n";
    $articles = $review->articles()->select('id', 'title', 'url')->get();
    echo "  Total articles: " . $articles->count() . "\n";
    
    if ($articles->count() > 0) {
        echo "  First 10 articles:\n";
        foreach ($articles->take(10) as $article) {
            $titlePreview = substr($article->title, 0, 60);
            $url = $article->url ?? 'no-url';
            echo "    [{$article->id}] {$titlePreview}... | URL: {$url}\n";
        }
    }
    
    // Check for exact title matches
    $titleGroups = $articles->groupBy(function($article) {
        return strtolower(trim($article->title));
    });
    
    $exactDuplicates = 0;
    foreach ($titleGroups as $title => $group) {
        if ($group->count() > 1) {
            $exactDuplicates += $group->count() - 1;
            echo "  EXACT DUPLICATE FOUND: '{$title}' appears {$group->count()} times\n";
            foreach ($group as $article) {
                echo "    - Article ID: {$article->id}\n";
            }
        }
    }
    
    // Check for URL matches
    $urlGroups = $articles->whereNotNull('url')->where('url', '!=', '')->groupBy('url');
    $urlDuplicates = 0;
    foreach ($urlGroups as $url => $group) {
        if ($group->count() > 1) {
            $urlDuplicates += $group->count() - 1;
            echo "  URL DUPLICATE FOUND: '{$url}' appears {$group->count()} times\n";
            foreach ($group as $article) {
                echo "    - Article ID: {$article->id} - {$article->title}\n";
            }
        }
    }
    
    echo "  Exact title duplicates: {$exactDuplicates}\n";
    echo "  URL duplicates: {$urlDuplicates}\n";
    echo "\n";
}

echo "=== Done ===\n";
