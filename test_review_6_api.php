<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

echo "=== Testing API for Review #6 ===\n\n";

// Try to find review #6
$review = Review::find(6);

if (!$review) {
    echo "Review #6 does not exist in the database!\n\n";
    
    echo "Testing what happens when we try to call the API...\n";
    
    // Simulate API call
    try {
        $user = User::first();
        
        // Try to get review
        $url = "http://localhost:8000/api/reviews/6";
        echo "GET {$url}\n";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Bearer fake-token-for-testing'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "HTTP Status: {$httpCode}\n";
        echo "Response: {$response}\n\n";
        
        // Try duplicate detection
        $url = "http://localhost:8000/api/reviews/6/articles/detect-duplicates";
        echo "POST {$url}\n";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Authorization: Bearer fake-token-for-testing'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "HTTP Status: {$httpCode}\n";
        echo "Response: {$response}\n";
        
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== Available Reviews ===\n";
$reviews = Review::all();
foreach ($reviews as $r) {
    echo "  - Review #{$r->id}: {$r->title} ({$r->articles()->count()} articles)\n";
}

echo "\n=== Recommendation ===\n";
echo "Please test duplicate detection on Review #2 (hello) which has 10,010 articles and 36+ duplicates.\n";
echo "URL: http://localhost:3000/reviews/2/data\n";
