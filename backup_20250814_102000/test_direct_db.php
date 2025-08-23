<?php
require_once 'config.php';

echo "=== DIRECT DATABASE TEST ===\n\n";

// Test the exact query from getEarningsDataWithMarketCap
$date = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT 
        e.report_date,
        e.ticker,
        e.report_time,
        e.eps_actual,
        e.eps_estimate,
        e.revenue_actual,
        e.revenue_estimate,
        m.market_cap,
        m.size,
        m.current_price,
        m.price_change_percent
    FROM EarningsTickersToday e
    LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
    WHERE e.report_date = ?
    ORDER BY CASE WHEN m.market_cap IS NULL THEN 0 ELSE 1 END DESC, m.market_cap DESC, e.report_time, e.ticker
    LIMIT 20
");
$stmt->execute([$date]);
$results = $stmt->fetchAll();

echo "🏆 FIRST 20 RECORDS FROM DATABASE:\n";
foreach ($results as $i => $row) {
    $marketCap = $row['market_cap'] ?? 0;
    $marketCapFormatted = $marketCap > 0 ? number_format($marketCap / 1000000000, 2) . 'B' : 'N/A';
    
    echo sprintf("   %2d. %-6s | Market Cap: %-10s | Price: $%-8.2f | NULL: %s\n", 
        $i + 1, 
        $row['ticker'], 
        $marketCapFormatted,
        $row['current_price'] ?? 0,
        $row['market_cap'] === null ? 'YES' : 'NO'
    );
}

// Check if there are any large market caps
echo "\n🔍 LARGEST MARKET CAPS:\n";
$stmt = $pdo->query("
    SELECT ticker, market_cap, current_price, size
    FROM TodayEarningsMovements 
    WHERE market_cap > 1000000000  -- > 1B
    ORDER BY market_cap DESC 
    LIMIT 10
");
$largeCaps = $stmt->fetchAll();

if (count($largeCaps) > 0) {
    foreach ($largeCaps as $row) {
        echo sprintf("   %-6s | %sB | $%.2f | %s\n", 
            $row['ticker'], 
            number_format($row['market_cap'] / 1000000000, 2),
            $row['current_price'],
            $row['size']
        );
    }
} else {
    echo "   No large market caps found (>1B)\n";
}
?>
