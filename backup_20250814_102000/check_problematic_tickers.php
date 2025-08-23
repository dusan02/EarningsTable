<?php
require_once 'D:/xampp/htdocs/earnings-table/config.php';
require_once 'D:/xampp/htdocs/earnings-table/common/Finnhub.php';
require_once 'utils/polygon_api_optimized.php';

echo "🔍 CHECKING PROBLEMATIC TICKERS\n";
echo "==============================\n\n";

$problematicTickers = ['MSI', 'FLUT', 'LNG', 'VST', 'PH', 'GILD'];

foreach ($problematicTickers as $ticker) {
    echo "📊 {$ticker}:\n";
    
    // Check Polygon V3
    $polygonData = getAccurateMarketCap($ticker);
    if ($polygonData && isset($polygonData['shares_outstanding'])) {
        echo "  ✅ Polygon V3: " . number_format($polygonData['shares_outstanding']) . " shares\n";
    } else {
        echo "  ❌ Polygon V3: No data\n";
    }
    
    // Check Finnhub
    $finnhubShares = Finnhub::getSharesOutstanding($ticker);
    if ($finnhubShares !== null) {
        echo "  ✅ Finnhub: " . number_format($finnhubShares) . "M shares\n";
    } else {
        echo "  ❌ Finnhub: No data\n";
    }
    
    // Check database
    $stmt = $pdo->prepare("SELECT * FROM TodayEarningsMovements WHERE ticker = ?");
    $stmt->execute([$ticker]);
    $dbData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dbData) {
        echo "  📊 Database: " . number_format($dbData['shares_outstanding']) . " shares\n";
        echo "  💰 Market Cap: $" . number_format($dbData['market_cap']) . "\n";
    } else {
        echo "  ❌ Database: Not found\n";
    }
    
    echo "\n";
}
?> 