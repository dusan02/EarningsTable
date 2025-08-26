<?php
require_once 'config.php';
require_once 'common/api_functions.php';

echo "=== TESTING POLYGON API ===\n";

// Test with a known ticker
$ticker = 'AAPL';
echo "Testing ticker: {$ticker}\n";

$result = getPolygonTickerDetails($ticker);
if ($result) {
    echo "✅ API call successful\n";
    echo "Result structure:\n";
    print_r($result);
} else {
    echo "❌ API call failed\n";
}

// Test with one of today's tickers
$ticker = 'MDB';
echo "\nTesting ticker: {$ticker}\n";

$result = getPolygonTickerDetails($ticker);
if ($result) {
    echo "✅ API call successful\n";
    echo "Market cap: " . ($result['market_cap'] ?? 'NOT FOUND') . "\n";
    echo "Company name: " . ($result['name'] ?? 'NOT FOUND') . "\n";
} else {
    echo "❌ API call failed\n";
}
?>
