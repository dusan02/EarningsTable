<?php
require_once 'config.php';
require_once 'common/Finnhub.php';
require_once 'common/api_functions.php';

echo "=== MARKET CAP TIMING ANALYSIS ===\n";

$testTicker = 'NVDA';

echo "Testing ticker: {$testTicker}\n\n";

// Get Finnhub market cap
$finnhub = new Finnhub();
$finnhubData = $finnhub->get('/stock/profile2', ['symbol' => $testTicker]);

if ($finnhubData) {
    $finnhubMarketCap = $finnhubData['marketCapitalization'] ?? null;
    $sharesOutstanding = $finnhubData['shareOutstanding'] ?? null;
    $companyName = $finnhubData['name'] ?? 'N/A';
    
    echo "=== FINNHUB DATA ===\n";
    echo "Company: {$companyName}\n";
    echo "Market Cap (Finnhub): {$finnhubMarketCap}\n";
    echo "Shares Outstanding: {$sharesOutstanding}\n";
    
    // Get current price from Polygon
    $polygonData = getPolygonBatchQuote([$testTicker]);
    
    if (isset($polygonData[$testTicker])) {
        $priceData = getCurrentPrice($polygonData[$testTicker]);
        $currentPrice = $priceData ? $priceData['price'] : null;
        $previousClose = $polygonData[$testTicker]['prevDay']['c'] ?? null;
        
        if ($currentPrice === null) {
            echo "❌ No valid current price found for {$testTicker}\n";
            exit(1);
        }
        
        echo "\n=== POLYGON DATA ===\n";
        echo "Current Price: \${$currentPrice}\n";
        echo "Previous Close: \${$previousClose}\n";
        
        // Calculate market cap from current price
        if ($sharesOutstanding && $currentPrice) {
            // Finnhub shares are in thousands, market cap is in millions
            $sharesInDollars = $sharesOutstanding * 1000;
            $calculatedMarketCap = $currentPrice * $sharesInDollars;
            $calculatedMarketCapMillions = $calculatedMarketCap / 1000000;
            
            echo "\n=== MARKET CAP COMPARISON ===\n";
            echo "Finnhub Market Cap: {$finnhubMarketCap} (millions = " . round($finnhubMarketCap / 1000, 2) . " billions)\n";
            echo "Calculated Market Cap: {$calculatedMarketCapMillions} (millions = " . round($calculatedMarketCapMillions / 1000, 2) . " billions)\n";
            echo "Shares Outstanding (actual): " . number_format($sharesInDollars) . "\n";
            echo "Difference: " . round($calculatedMarketCapMillions - $finnhubMarketCap, 2) . " millions\n";
            echo "Percentage Difference: " . round((($calculatedMarketCapMillions - $finnhubMarketCap) / $finnhubMarketCap) * 100, 2) . "%\n";
            
            // Check if Finnhub market cap is based on current or previous close
            $previousCloseMarketCap = $previousClose * $sharesInDollars / 1000000;
            echo "\nPrevious Close Market Cap: {$previousCloseMarketCap} (millions)\n";
            
            if (abs($calculatedMarketCapMillions - $finnhubMarketCap) < abs($previousCloseMarketCap - $finnhubMarketCap)) {
                echo "✅ Finnhub market cap appears to be CURRENT (based on current price)\n";
            } else {
                echo "📅 Finnhub market cap appears to be PREVIOUS (based on previous close)\n";
            }
        }
    } else {
        echo "❌ Failed to get Polygon data\n";
    }
} else {
    echo "❌ Failed to get Finnhub data\n";
}
?>
