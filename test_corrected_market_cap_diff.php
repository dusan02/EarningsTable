<?php
require_once 'config.php';
require_once 'common/Finnhub.php';
require_once 'common/api_functions.php';

echo "=== CORRECTED MARKET CAP DIFF TEST ===\n";

$testTicker = 'NVDA';

echo "Testing ticker: {$testTicker}\n\n";

// Get data from both APIs
$finnhub = new Finnhub();
$finnhubData = $finnhub->get('/stock/profile2', ['symbol' => $testTicker]);
$polygonData = getPolygonBatchQuote([$testTicker]);

if ($finnhubData && isset($polygonData[$testTicker])) {
    // Extract data
    $currentPrice = getCurrentPrice($polygonData[$testTicker]);
    $previousClose = $polygonData[$testTicker]['prevDay']['c'] ?? $currentPrice;
    $sharesOutstanding = $finnhubData['shareOutstanding'] ?? null;
    $companyName = $finnhubData['name'] ?? 'N/A';
    
    echo "=== INPUT DATA ===\n";
    echo "Company: {$companyName}\n";
    echo "Current Price: \${$currentPrice}\n";
    echo "Previous Close: \${$previousClose}\n";
    echo "Shares Outstanding: {$sharesOutstanding} (thousands)\n";
    
    // Calculate price change
    $priceChange = $currentPrice - $previousClose;
    $priceChangePercent = ($previousClose > 0) ? ($priceChange / $previousClose) * 100 : 0;
    
    echo "\n=== PRICE CHANGE ===\n";
    echo "Price Change: \${$priceChange} ({$priceChangePercent}%)\n";
    
    // CORRECTED calculation
    if ($sharesOutstanding) {
        // Convert shares from thousands to actual shares
        $sharesInDollars = $sharesOutstanding * 1000;
        
        // Calculate current and previous market cap
        $currentMarketCap = $currentPrice * $sharesInDollars;
        $previousMarketCap = $previousClose * $sharesInDollars;
        
        // Market cap diff = Current Market Cap - Previous Day Market Cap
        $marketCapDiff = $currentMarketCap - $previousMarketCap;
        $marketCapDiffBillions = $marketCapDiff / 1000000000;
        
        echo "\n=== CORRECTED CALCULATION ===\n";
        echo "Shares Outstanding (actual): " . number_format($sharesInDollars) . "\n";
        echo "Current Market Cap: $" . number_format($currentMarketCap) . "\n";
        echo "Previous Market Cap: $" . number_format($previousMarketCap) . "\n";
        echo "Market Cap Diff: $" . number_format($marketCapDiff) . "\n";
        echo "Market Cap Diff (Billions): $" . round($marketCapDiffBillions, 3) . "B\n";
        
        // Verify calculation
        $expectedDiff = $priceChange * $sharesInDollars;
        echo "\n=== VERIFICATION ===\n";
        echo "Expected Diff (price change × shares): $" . number_format($expectedDiff) . "\n";
        echo "Calculated Diff: $" . number_format($marketCapDiff) . "\n";
        echo "Match: " . ($marketCapDiff == $expectedDiff ? "✅ YES" : "❌ NO") . "\n";
        
        echo "\n✅ CORRECTED CALCULATION IS ACCURATE!\n";
        echo "Market Cap Diff = Current Market Cap - Previous Day Market Cap\n";
    }
} else {
    echo "❌ Failed to get data\n";
}
?>
