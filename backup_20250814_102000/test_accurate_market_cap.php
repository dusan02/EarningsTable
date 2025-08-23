<?php
require_once 'D:/xampp/htdocs/earnings-table/config.php';
require_once 'utils/polygon_api_optimized.php';

echo "🔍 TESTING ACCURATE MARKET CAP FOR LLY\n";
echo "=====================================\n\n";

$accurateData = getAccurateMarketCap('LLY');

if ($accurateData) {
    echo "✅ ACCURATE DATA FROM POLYGON V3:\n";
    echo "  📊 Market Cap: $" . number_format($accurateData['market_cap']) . "\n";
    echo "  📈 Market Cap (B$): $" . round($accurateData['market_cap'] / 1000000000, 1) . "B\n";
    echo "  🎯 Shares Outstanding: " . number_format($accurateData['shares_outstanding']) . "\n";
    echo "  🏢 Company Name: " . $accurateData['company_name'] . "\n";
    
    // Calculate current price from market cap and shares
    $currentPrice = $accurateData['market_cap'] / $accurateData['shares_outstanding'];
    echo "  💰 Calculated Current Price: $" . round($currentPrice, 2) . "\n";
    
} else {
    echo "❌ Failed to get accurate data\n";
}
?> 