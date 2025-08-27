<?php
require_once 'config.php';
require_once 'common/Finnhub.php';
require_once 'common/api_functions.php';

echo "=== FIX MARKET CAP CALCULATION ===\n";

// Test with one ticker first
$testTicker = 'NVDA';

echo "Testing ticker: {$testTicker}\n\n";

// STEP 1: Get price from Polygon
echo "=== STEP 1: GET PRICE FROM POLYGON ===\n";
$batchData = getPolygonBatchQuote([$testTicker]);

if ($batchData && isset($batchData[$testTicker])) {
    $tickerData = $batchData[$testTicker];
    $currentPrice = getCurrentPrice($tickerData);
    echo "✅ Current Price: {$currentPrice}\n";
} else {
    echo "❌ Failed to get price from Polygon\n";
    exit(1);
}

// STEP 2: Get shares outstanding from Finnhub
echo "\n=== STEP 2: GET SHARES OUTSTANDING FROM FINNHUB ===\n";
$finnhub = new Finnhub();
$sharesOutstanding = $finnhub->getSharesOutstanding($testTicker);

if ($sharesOutstanding) {
    echo "✅ Shares Outstanding: " . number_format($sharesOutstanding) . "\n";
} else {
    echo "❌ Failed to get shares outstanding from Finnhub\n";
    exit(1);
}

// STEP 3: Calculate market cap
echo "\n=== STEP 3: CALCULATE MARKET CAP ===\n";
$marketCap = $currentPrice * $sharesOutstanding;
echo "✅ Market Cap: $" . number_format($marketCap) . " (" . round($marketCap / 1000000000, 2) . "B)\n";

// STEP 4: Determine size
echo "\n=== STEP 4: DETERMINE SIZE ===\n";
$size = 'Small';
if ($marketCap >= 10000000000) { // 10B+
    $size = 'Large';
} elseif ($marketCap >= 2000000000) { // 2B+
    $size = 'Mid';
}

echo "✅ Size: {$size}\n";

echo "\n=== SOLUTION ===\n";
echo "Market cap sa musí počítať: Price × Shares Outstanding\n";
echo "Polygon API neposkytuje market cap priamo, len ceny\n";
echo "Finnhub poskytuje shares outstanding\n";
echo "Kombinácia oboch = správny market cap\n";
?>
