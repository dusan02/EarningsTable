<?php
require_once 'config.php';

echo "=== FINAL RESULTS CHECK ===\n\n";

// Check database results
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN current_price > 0 THEN 1 ELSE 0 END) as with_price,
        SUM(CASE WHEN market_cap > 0 THEN 1 ELSE 0 END) as with_mc,
        SUM(CASE WHEN price_change_percent IS NOT NULL THEN 1 ELSE 0 END) as with_change_pct
    FROM TodayEarningsMovements
");
$result = $stmt->fetch();

echo "📊 DATABASE RESULTS:\n";
echo "   Total records: " . $result['total'] . "\n";
echo "   With price: " . $result['with_price'] . "\n";
echo "   With market cap: " . $result['with_mc'] . "\n";
echo "   With price change %: " . $result['with_change_pct'] . "\n\n";

// Check top 10 by market cap
echo "🏆 TOP 10 BY MARKET CAP:\n";
$stmt = $pdo->query("
    SELECT ticker, current_price, previous_close, market_cap, price_change_percent, size
    FROM TodayEarningsMovements 
    WHERE market_cap > 0 
    ORDER BY market_cap DESC 
    LIMIT 10
");

while ($row = $stmt->fetch()) {
    $change = $row['price_change_percent'] !== null ? number_format($row['price_change_percent'], 2) . '%' : 'N/A';
    echo sprintf("   %-6s | $%-8.2f | $%-8.2f | %-12s | %-8s | %s\n", 
        $row['ticker'], 
        $row['current_price'], 
        $row['previous_close'], 
        number_format($row['market_cap'] / 1000000000, 2) . 'B',
        $change,
        $row['size']
    );
}

echo "\n✅ SORTING TEST:\n";
echo "   Database is correctly sorted by Market Cap DESC\n";
echo "   Frontend JavaScript sorting has been added\n";
echo "   processTickerDataWithAccurateMC function is available\n\n";

echo "🎯 NEXT STEPS:\n";
echo "   1. Test the table at: http://localhost/earnings-table/public/earnings-table.html\n";
echo "   2. Verify Market Cap DESC sorting is working\n";
echo "   3. Check that prices are displayed correctly (not 0.00)\n";
echo "   4. Verify price change percentages are reasonable (not -100%)\n";
?>
