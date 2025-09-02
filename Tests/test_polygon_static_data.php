<?php
require_once 'config.php';
require_once 'common/api_functions.php';

echo "=== POLYGON STATIC DATA TEST ===\n";

$testTickers = ['NVDA', 'AAPL', 'MSFT'];

foreach ($testTickers as $ticker) {
    echo "\n--- Testing {$ticker} ---\n";
    
    // Test Polygon Ticker Details (V3 Reference API)
    echo "Testing Polygon Ticker Details (V3 Reference)...\n";
    $details = getPolygonTickerDetails($ticker);
    
    if ($details) {
        echo "✅ Polygon Ticker Details found\n";
        echo "  Market Cap: " . ($details['market_cap'] ?? 'N/A') . "\n";
        echo "  Company Name: " . ($details['name'] ?? 'N/A') . "\n";
        echo "  Shares Outstanding: " . ($details['weighted_shares_outstanding'] ?? 'N/A') . "\n";
        echo "  Type: " . ($details['type'] ?? 'N/A') . "\n";
        echo "  Primary Exchange: " . ($details['primary_exchange'] ?? 'N/A') . "\n";
    } else {
        echo "❌ Polygon Ticker Details not found\n";
    }
    
    // Test Polygon Batch Quote (V2 Snapshot API)
    echo "\nTesting Polygon Batch Quote (V2 Snapshot)...\n";
    $batchData = getPolygonBatchQuote([$ticker]);
    
    if ($batchData && isset($batchData[$ticker])) {
        echo "✅ Polygon Batch Quote found\n";
        $tickerData = $batchData[$ticker];
        
        echo "  Ticker: " . ($tickerData['ticker'] ?? 'N/A') . "\n";
        echo "  Current Price: " . (getCurrentPrice($tickerData) ?? 'N/A') . "\n";
        echo "  Previous Close: " . ($tickerData['prevDay']['c'] ?? 'N/A') . "\n";
        
        // Check if market data exists in batch response
        if (isset($tickerData['market'])) {
            echo "  Market Cap (from batch): " . ($tickerData['market']['market_cap'] ?? 'N/A') . "\n";
        } else {
            echo "  Market Cap (from batch): Not available in batch response\n";
        }
    } else {
        echo "❌ Polygon Batch Quote not found\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "Polygon V3 Reference API provides:\n";
echo "✅ Market Cap (static)\n";
echo "✅ Company Name (static)\n";
echo "✅ Shares Outstanding (static)\n";
echo "✅ Company Type (static)\n";
echo "✅ Primary Exchange (static)\n";
echo "\nPolygon V2 Snapshot API provides:\n";
echo "✅ Current Price (dynamic)\n";
echo "✅ Previous Close (static)\n";
echo "❌ Market Cap (not available in batch)\n";
echo "❌ Company Name (only ticker symbol)\n";
echo "❌ Shares Outstanding (not available)\n";
?>
