<?php
require_once 'config.php';
require_once 'common/api_functions.php';
require_once 'test_helper.php';

echo "=== TESTING POLYGON API ===\n";

// Test with current tickers
$testTickers = TestHelper::getCurrentTickers(2);
TestHelper::printTickerInfo($testTickers, "Testing Polygon API for");

foreach ($testTickers as $ticker) {
    echo "Testing ticker: {$ticker}\n";

    $result = getPolygonTickerDetails($ticker);
    if ($result) {
        echo "✅ API call successful\n";
        echo "Market cap: " . ($result['market_cap'] ?? 'NOT FOUND') . "\n";
        echo "Company name: " . ($result['name'] ?? 'NOT FOUND') . "\n";
    } else {
        echo "❌ API call failed\n";
    }
    echo "\n";
}
?>
