<?php
require_once 'config.php';
require_once 'common/api_functions.php';

echo "=== TESTING POLYGON API WITH BMO.TO AND BNS.TO ===\n";

$tickers = ['BMO.TO', 'BNS.TO'];

foreach ($tickers as $ticker) {
    echo "\n--- Testing {$ticker} ---\n";
    
    // Test ticker details
    echo "Testing getPolygonTickerDetails...\n";
    $details = getPolygonTickerDetails($ticker);
    if ($details) {
        echo "✅ Ticker details found\n";
        echo "Market cap: " . ($details['market_cap'] ?? 'N/A') . "\n";
        echo "Name: " . ($details['name'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Ticker details not found\n";
    }
    
    // Test batch quote
    echo "Testing getPolygonBatchQuote...\n";
    $batchData = getPolygonBatchQuote([$ticker]);
    if ($batchData && isset($batchData[$ticker])) {
        echo "✅ Batch quote found\n";
        $currentPrice = getCurrentPrice($batchData[$ticker]);
        echo "Current price: " . ($currentPrice ?? 'N/A') . "\n";
    } else {
        echo "❌ Batch quote not found\n";
    }
}
?>
