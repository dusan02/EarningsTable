<?php
require_once 'config.php';

echo "=== TESTING POLYGON NEWS API FOR DCI ===\n";

// Test with DCI ticker
$ticker = 'DCI';
echo "Testing ticker: {$ticker}\n";

// Use the working API key from the codebase
$apiKey = 'Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX';

// Polygon news endpoint
$url = "https://api.polygon.io/v2/reference/news?ticker={$ticker}&apiKey={$apiKey}";

echo "URL: {$url}\n\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 30,
        'header' => [
            'User-Agent: EarningsTable/1.0',
            'Accept: application/json'
        ]
    ]
]);

$startTime = microtime(true);
$response = file_get_contents($url, false, $context);
$endTime = microtime(true);

if ($response === false) {
    echo "❌ API call failed\n";
    exit(1);
}

$timeToFirstByte = round(($endTime - $startTime) * 1000, 2);
echo "⏱️  Response time: {$timeToFirstByte}ms\n";

$data = json_decode($response, true);

if (!$data) {
    echo "❌ Failed to decode JSON response\n";
    echo "Raw response:\n";
    echo $response . "\n";
    exit(1);
}

echo "✅ API call successful\n\n";

echo "=== RESPONSE STRUCTURE ===\n";
echo "Keys: " . implode(', ', array_keys($data)) . "\n\n";

if (isset($data['results'])) {
    echo "📰 Found " . count($data['results']) . " news articles\n\n";
    
    // Show first 3 articles
    $count = 0;
    foreach ($data['results'] as $article) {
        if ($count >= 3) break;
        
        echo "--- Article " . ($count + 1) . " ---\n";
        echo "Title: " . ($article['title'] ?? 'N/A') . "\n";
        echo "Published: " . ($article['published_utc'] ?? 'N/A') . "\n";
        echo "URL: " . ($article['article_url'] ?? 'N/A') . "\n";
        echo "Description: " . (substr($article['description'] ?? 'N/A', 0, 100)) . "...\n";
        echo "Tickers: " . implode(', ', $article['tickers'] ?? []) . "\n";
        echo "\n";
        
        $count++;
    }
} else {
    echo "❌ No 'results' key in response\n";
    echo "Full response:\n";
    echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
}

echo "=== FULL RESPONSE ===\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
?>
