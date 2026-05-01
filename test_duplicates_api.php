<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Review;
use App\Http\Controllers\Api\ArticleController;
use Illuminate\Http\Request;

echo "=== Testing Duplicate Detection API ===\n\n";

// Get review #2 (the one with 10010 articles)
$review = Review::find(2);

if (!$review) {
    echo "Review #2 not found!\n";
    exit(1);
}

echo "Review: {$review->title}\n";
echo "Articles: " . $review->articles()->count() . "\n\n";

// Create a mock request
$request = new Request();
$request->setUserResolver(function () use ($review) {
    return $review->user;
});

// Create controller instance
$controller = new ArticleController();

echo "Calling detectDuplicates...\n";
$startTime = microtime(true);

try {
    $response = $controller->detectDuplicates($review, $request);
    $data = json_decode($response->getContent(), true);
    
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "\n=== RESPONSE ===\n";
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Duration: {$duration}s\n";
    echo "Message: " . ($data['message'] ?? 'N/A') . "\n";
    echo "\n";
    
    if (isset($data['data'])) {
        echo "Data structure:\n";
        echo "  - duplicates: " . (isset($data['data']['duplicates']) ? count($data['data']['duplicates']) : 'NOT SET') . "\n";
        echo "  - total_duplicates: " . ($data['data']['total_duplicates'] ?? 'NOT SET') . "\n";
        echo "  - execution_time: " . ($data['data']['execution_time'] ?? 'NOT SET') . "\n";
        echo "  - articles_checked: " . ($data['data']['articles_checked'] ?? 'NOT SET') . "\n";
        
        if (isset($data['data']['duplicates']) && count($data['data']['duplicates']) > 0) {
            echo "\nFirst 3 duplicates:\n";
            foreach (array_slice($data['data']['duplicates'], 0, 3) as $i => $dup) {
                echo "  " . ($i + 1) . ". Article {$dup['article1_id']} vs {$dup['article2_id']}\n";
                echo "     Similarity: {$dup['similarity']}%\n";
                echo "     Reason: {$dup['reason']}\n";
                echo "     Title 1: " . substr($dup['article1_title'], 0, 60) . "...\n";
                echo "     Title 2: " . substr($dup['article2_title'], 0, 60) . "...\n";
                echo "\n";
            }
        }
    } else {
        echo "No 'data' key in response!\n";
        echo "Full response:\n";
        print_r($data);
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Done ===\n";
