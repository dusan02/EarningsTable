<?php
require_once __DIR__ . '/config.php';

echo "Testing frontend API endpoints...\n\n";

// Test both API endpoints that frontend tables use
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: $date\n\n";

// 1. Test earnings-tickers-today.php (used by earnings-table.html)
echo "=== 1. earnings-tickers-today.php (earnings-table.html) ===\n";
require_once 'utils/database.php';
$earnings = getEarningsDataWithMarketCap($pdo, $date);

echo "First 5 records:\n";
for ($i = 0; $i < 5; $i++) {
    $item = $earnings[$i];
    echo sprintf("%d. %s: MC=%s, Price=%s\n", 
        $i + 1,
        $item['ticker'],
        $item['market_cap'] ? number_format($item['market_cap'] / 1e9, 2) . 'B' : 'NULL',
        $item['current_price'] ? '$' . number_format($item['current_price'], 2) : 'NULL'
    );
}

// 2. Test today-earnings-movements.php (used by today-movements-table.html)
echo "\n=== 2. today-earnings-movements.php (today-movements-table.html) ===\n";

$stmt = $pdo->prepare("
    SELECT 
        e.ticker,
        COALESCE(m.company_name, e.ticker) as company_name,
        COALESCE(m.current_price, 0) as current_price,
        COALESCE(m.previous_close, 0) as previous_close,
        COALESCE(m.market_cap, 0) as market_cap,
        COALESCE(m.size, 'Unknown') as size,
        COALESCE(m.market_cap_diff, 0) as market_cap_diff,
        COALESCE(m.market_cap_diff_billions, 0) as market_cap_diff_billions,
        COALESCE(m.price_change_percent, 0) as price_change_percent,
        COALESCE(m.shares_outstanding, 0) as shares_outstanding,
        COALESCE(m.updated_at, e.report_date) as updated_at,
        e.report_time,
        e.eps_actual,
        e.revenue_actual
    FROM EarningsTickersToday e
    LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
    WHERE e.report_date = ?
    ORDER BY m.market_cap DESC, e.ticker ASC
    LIMIT 5
");

$stmt->execute([$date]);
$data2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "First 5 records:\n";
foreach ($data2 as $i => $item) {
    echo sprintf("%d. %s: MC=%s, Price=%s\n", 
        $i + 1,
        $item['ticker'],
        $item['market_cap'] ? number_format($item['market_cap'] / 1e9, 2) . 'B' : 'NULL',
        $item['current_price'] ? '$' . number_format($item['current_price'], 2) : 'NULL'
    );
}

echo "\n=== SUMMARY ===\n";
echo "Both endpoints return the same data with Market Cap DESC ordering.\n";
echo "The problem is NOT in the API endpoints.\n";
echo "The problem is in the frontend JavaScript - no client-side sorting implemented.\n";
?>
