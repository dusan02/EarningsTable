<?php
require 'config.php';

try {
    
    // Check today's data
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM EarningsTickersToday WHERE report_date = CURDATE()");
    $total = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as with_mc FROM TodayEarningsMovements WHERE market_cap IS NOT NULL AND market_cap > 0");
    $with_mc = $stmt->fetch()['with_mc'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as with_price FROM TodayEarningsMovements WHERE current_price IS NOT NULL");
    $with_price = $stmt->fetch()['with_price'];
    
    echo "📊 Total tickers today: $total\n";
    echo "📊 With Market Cap: $with_mc\n";
    echo "📊 With Current Price: $with_price\n";
    
    // Show top 5 by market cap
    echo "\n🏆 TOP 5 BY MARKET CAP:\n";
    $stmt = $pdo->query("SELECT ticker, market_cap, current_price FROM TodayEarningsMovements WHERE market_cap IS NOT NULL AND market_cap > 0 ORDER BY market_cap DESC LIMIT 5");
    while ($row = $stmt->fetch()) {
        $mc_b = round($row['market_cap'] / 1000000000, 2);
        echo "  {$row['ticker']}: \${$mc_b}B (\${$row['current_price']})\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
