<?php
require_once 'config.php';
require_once 'common/Finnhub.php';
require_once 'common/api_functions.php';

echo "=== FINAL MARKET CAP DIFF TEST ===\n";

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
    $finnhubMarketCap = $finnhubData['marketCapitalization'] ?? null;
    $companyName = $finnhubData['name'] ?? 'N/A';
    
    echo "=== INPUT DATA ===\n";
    echo "Company: {$companyName}\n";
    echo "Current Price: \${$currentPrice}\n";
    echo "Previous Close: \${$previousClose}\n";
    echo "Finnhub Market Cap: {$finnhubMarketCap} (millions)\n";
    
    // Calculate price change
    $priceChange = $currentPrice - $previousClose;
    $priceChangePercent = ($previousClose > 0) ? ($priceChange / $previousClose) * 100 : 0;
    
    echo "\n=== PRICE CHANGE ===\n";
    echo "Price Change: \${$priceChange} ({$priceChangePercent}%)\n";
    
    // CORRECT calculation (percentage method)
    if ($finnhubMarketCap) {
        $marketCapInDollars = $finnhubMarketCap * 1000000; // Convert millions to dollars
        $marketCapDiff = ($priceChangePercent / 100) * $marketCapInDollars;
        $marketCapDiffBillions = $marketCapDiff / 1000000000;
        
        echo "\n=== CORRECT CALCULATION (percentage method) ===\n";
        echo "Market Cap in Dollars: $" . number_format($marketCapInDollars) . "\n";
        echo "Price Change %: {$priceChangePercent}%\n";
        echo "Market Cap Diff: $" . number_format($marketCapDiff) . "\n";
        echo "Market Cap Diff (Billions): $" . round($marketCapDiffBillions, 2) . "B\n";
        
        // Compare with dashboard
        echo "\n=== COMPARISON WITH DASHBOARD ===\n";
        echo "Dashboard Market Cap Gain: $5.31B\n";
        echo "Our Calculation: $" . round($marketCapDiffBillions, 2) . "B\n";
        $difference = abs($marketCapDiffBillions - 5.31);
        echo "Difference: $" . round($difference, 2) . "B\n";
        
        if ($difference < 0.5) {
            echo "✅ CALCULATION MATCHES DASHBOARD!\n";
        } else {
            echo "⚠️  CALCULATION DIFFERS FROM DASHBOARD\n";
        }
        
        echo "\n✅ CORRECT CALCULATION: Market Cap Diff = (Price Change % / 100) × Market Cap\n";
    }
} else {
    echo "❌ Failed to get data\n";
}
?>
