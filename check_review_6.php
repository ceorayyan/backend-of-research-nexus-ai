<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Review;

echo "=== Checking Review #6 ===\n\n";

$review = Review::find(6);

if (!$review) {
    echo "Review #6 not found!\n";
    echo "\nAvailable reviews:\n";
    $reviews = Review::all();
    foreach ($reviews as $r) {
        echo "  - Review #{$r->id}: {$r->title} ({$r->articles()->count()} articles)\n";
    }
    exit(1);
}

echo "Review ID: {$review->id}\n";
echo "Title: {$review->title}\n";
echo "Articles: " . $review->articles()->count() . "\n\n";

// Check for duplicates
$articles = $review->articles()->select('id', 'title', 'url')->get();

// Check exact title duplicates
$titleGroups = $articles->groupBy(function($article) {
    return strtolower(trim($article->title));
});

$exactDuplicates = 0;
$duplicatePairs = [];

foreach ($titleGroups as $title => $group) {
    if ($group->count() > 1) {
        $exactDuplicates += ($group->count() - 1);
        $duplicatePairs[] = [
            'title' => $title,
            'count' => $group->count(),
            'ids' => $group->pluck('id')->toArray()
        ];
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

echo "Duplicate Summary:\n";
echo "  - Exact title duplicates: {$exactDuplicates}\n";
echo "  - URL duplicates: {$urlDuplicates}\n";
echo "  - Total: " . ($exactDuplicates + $urlDuplicates) . "\n\n";

if (count($duplicatePairs) > 0) {
    echo "First 10 duplicate groups:\n";
    foreach (array_slice($duplicatePairs, 0, 10) as $i => $dup) {
        $titlePreview = substr($dup['title'], 0, 70);
        echo "  " . ($i + 1) . ". \"{$titlePreview}...\" ({$dup['count']} copies)\n";
        echo "     Article IDs: " . implode(', ', $dup['ids']) . "\n";
    }
} else {
    echo "No duplicates found in this review.\n";
}

echo "\n=== Done ===\n";
