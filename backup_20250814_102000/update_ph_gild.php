<?php
require_once 'D:/xampp/htdocs/earnings-table/config.php';
require_once 'D:/xampp/htdocs/earnings-table/common/Finnhub.php';
require_once 'utils/polygon_api_optimized.php';

echo "🔧 UPDATING PH AND GILD WITH ACCURATE DATA\n";
echo "==========================================\n\n";

$tickers = ['PH', 'GILD'];

foreach ($tickers as $ticker) {
    echo "📊 {$ticker}:\n";
    
    // Get accurate shares outstanding
    $sharesOutstanding = getSharesOutstandingEnhanced($ticker);
    
    if ($sharesOutstanding !== null) {
        echo "  ✅ Shares Outstanding: " . number_format($sharesOutstanding) . "\n";
        
        // Get current price from database
        $stmt = $pdo->prepare("SELECT current_price FROM TodayEarningsMovements WHERE ticker = ?");
        $stmt->execute([$ticker]);
        $currentPrice = $stmt->fetchColumn();
        
        if ($currentPrice) {
            // Calculate accurate market cap
            $accurateMarketCap = $currentPrice * $sharesOutstanding;
            echo "  💰 Current Price: $" . number_format($currentPrice, 2) . "\n";
            echo "  📊 Accurate Market Cap: $" . number_format($accurateMarketCap) . "\n";
            
            // Update database
            $updateStmt = $pdo->prepare("
                UPDATE TodayEarningsMovements 
                SET shares_outstanding = ?, market_cap = ?, updated_at = CURRENT_TIMESTAMP
                WHERE ticker = ?
            ");
            $updateStmt->execute([$sharesOutstanding, $accurateMarketCap, $ticker]);
            
            echo "  ✅ Database updated successfully!\n";
        } else {
            echo "  ❌ No current price found in database\n";
        }
    } else {
        echo "  ❌ No shares outstanding data available\n";
    }
    
    echo "\n";
}
?> 