<?php
require_once 'config.php';
require_once 'common/Finnhub.php';
require_once 'common/api_functions.php';

echo "=== MARKET CAP DIFF CALCULATION TEST ===\n";

$testTicker = 'NVDA';

echo "Testing ticker: {$testTicker}\n\n";

// Get data from both APIs
$finnhub = new Finnhub();
$finnhubData = $finnhub->get('/stock/profile2', ['symbol' => $testTicker]);
$polygonData = getPolygonBatchQuote([$testTicker]);

if ($finnhubData && isset($polygonData[$testTicker])) {
    // Extract data
    $finnhubMarketCap = $finnhubData['marketCapitalization'] ?? null; // in millions
    $priceData = getCurrentPrice($polygonData[$testTicker]);
    $currentPrice = $priceData ? $priceData['price'] : null;
    $previousClose = $polygonData[$testTicker]['prevDay']['c'] ?? null;
    
    if ($currentPrice === null) {
        echo "❌ No valid current price found for {$testTicker}\n";
        exit(1);
    }
    
    echo "=== INPUT DATA ===\n";
    echo "Current Price: \${$currentPrice}\n";
    echo "Previous Close: \${$previousClose}\n";
    echo "Finnhub Market Cap: {$finnhubMarketCap} (millions)\n";
    
    // Calculate price change
    $priceChange = $currentPrice - $previousClose;
    $priceChangePercent = ($previousClose > 0) ? ($priceChange / $previousClose) * 100 : 0;
    
    echo "\n=== CALCULATIONS ===\n";
    echo "Price Change: \${$priceChange} ({$priceChangePercent}%)\n";
    
    // Current calculation (from cron)
    $marketCapInDollars = $finnhubMarketCap * 1000000; // Convert millions to dollars
    $marketCapDiff = ($priceChangePercent / 100) * $marketCapInDollars;
    $marketCapDiffBillions = $marketCapDiff / 1000000000;
    
    echo "\n=== CURRENT CALCULATION (from cron) ===\n";
    echo "Market Cap in Dollars: $" . number_format($marketCapInDollars) . "\n";
    echo "Market Cap Diff: $" . number_format($marketCapDiff) . "\n";
    echo "Market Cap Diff (Billions): $" . round($marketCapDiffBillions, 2) . "B\n";
    
    // Alternative calculation: Direct difference
    $sharesOutstanding = $finnhubData['shareOutstanding'] ?? null;
    if ($sharesOutstanding) {
        $sharesInDollars = $sharesOutstanding * 1000; // Convert thousands to actual shares
        
        $currentMarketCap = $currentPrice * $sharesInDollars;
        $previousMarketCap = $previousClose * $sharesInDollars;
        $directMarketCapDiff = $currentMarketCap - $previousMarketCap;
        $directMarketCapDiffBillions = $directMarketCapDiff / 1000000000;
        
        echo "\n=== ALTERNATIVE CALCULATION (direct difference) ===\n";
        echo "Shares Outstanding: " . number_format($sharesInDollars) . "\n";
        echo "Current Market Cap: $" . number_format($currentMarketCap) . "\n";
        echo "Previous Market Cap: $" . number_format($previousMarketCap) . "\n";
        echo "Direct Market Cap Diff: $" . number_format($directMarketCapDiff) . "\n";
        echo "Direct Market Cap Diff (Billions): $" . round($directMarketCapDiffBillions, 2) . "B\n";
        
        // Compare methods
        echo "\n=== COMPARISON ===\n";
        $difference = abs($marketCapDiff - $directMarketCapDiff);
        $percentageDiff = ($difference / $directMarketCapDiff) * 100;
        
        echo "Difference between methods: $" . number_format($difference) . "\n";
        echo "Percentage difference: " . round($percentageDiff, 4) . "%\n";
        
        if ($percentageDiff < 0.1) {
            echo "✅ CALCULATION IS CORRECT! Methods match within 0.1%\n";
        } else {
            echo "❌ CALCULATION MIGHT BE INCORRECT! Methods differ by " . round($percentageDiff, 2) . "%\n";
        }
        
        // Show what the calculation represents
        echo "\n=== WHAT THIS MEANS ===\n";
        echo "Current method calculates: How much market cap changed due to price change\n";
        echo "Direct method calculates: Actual difference between current and previous market cap\n";
        echo "Both should be the same if Finnhub market cap is truly current!\n";
    }
} else {
    echo "❌ Failed to get data\n";
}
?>
