<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Review;

echo "=== All Reviews ===\n\n";

$reviews = Review::with('user')->get();

foreach ($reviews as $review) {
    $articleCount = $review->articles()->count();
    
    echo "Review ID: {$review->id}\n";
    echo "Title: {$review->title}\n";
    echo "Type: {$review->type}\n";
    echo "Domain: {$review->domain}\n";
    echo "Owner: {$review->user->name} ({$review->user->email})\n";
    echo "Articles: {$articleCount}\n";
    
    // Quick duplicate check
    $articles = $review->articles()->select('id', 'title', 'url')->get();
    
    // Check exact title duplicates
    $titleGroups = $articles->groupBy(function($article) {
        return strtolower(trim($article->title));
    });
    
    $exactDuplicates = 0;
    foreach ($titleGroups as $title => $group) {
        if ($group->count() > 1) {
            $exactDuplicates += ($group->count() - 1);
        }
    }
    
    // Check URL duplicates
    $urlGroups = $articles->whereNotNull('url')->where('url', '!=', '')->groupBy('url');
    $urlDuplicates = 0;
    foreach ($urlGroups as $url => $group) {
        if ($group->count() > 1) {
            $urlDuplicates += ($group->count() - 1);
        }
    }
    
    $totalDuplicates = $exactDuplicates + $urlDuplicates;
    
    echo "Estimated Duplicates: {$totalDuplicates}\n";
    echo "  - Exact title: {$exactDuplicates}\n";
    echo "  - Same URL: {$urlDuplicates}\n";
    echo "\n";
    echo str_repeat("-", 60) . "\n\n";
}

echo "=== Summary ===\n";
echo "Total reviews: " . $reviews->count() . "\n";
echo "\nTo test duplicate detection, use Review ID 2 (hello) which has duplicates.\n";
