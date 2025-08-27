<?php
require_once 'config.php';

echo "=== API ENDPOINT TEST ===\n\n";

// Get current date in US Eastern Time
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

// Test the exact API query
$stmt = $pdo->prepare("
    SELECT 
        t.ticker,
        t.company_name,
        t.current_price,
        t.previous_close,
        t.market_cap,
        t.size,
        t.market_cap_diff,
        t.market_cap_diff_billions,
        t.price_change_percent,
        t.shares_outstanding,
        e.eps_estimate,
        t.eps_actual,
        e.revenue_estimate,
        t.revenue_actual,
        t.updated_at,
        e.report_time
    FROM TodayEarningsMovements t
    LEFT JOIN EarningsTickersToday e ON t.ticker = e.ticker AND e.report_date = ?
    WHERE t.ticker = 'ATAT'
    ORDER BY t.market_cap_diff_billions DESC, t.ticker
");

$stmt->execute([$date]);
$atatData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($atatData) {
    echo "=== ATAT DATA ===\n";
    echo "Ticker: " . $atatData['ticker'] . "\n";
    echo "EPS Estimate: " . ($atatData['eps_estimate'] ?: 'NULL') . "\n";
    echo "EPS Actual: " . ($atatData['eps_actual'] ?: 'NULL') . "\n";
    echo "Revenue Estimate: " . ($atatData['revenue_estimate'] ?: 'NULL') . "\n";
    echo "Revenue Actual: " . ($atatData['revenue_actual'] ?: 'NULL') . "\n";
    echo "Market Cap: " . ($atatData['market_cap'] ?: 'NULL') . "\n";
    echo "Price: " . ($atatData['current_price'] ?: 'NULL') . "\n";
    echo "Updated: " . ($atatData['updated_at'] ?: 'NULL') . "\n";
} else {
    echo "❌ ATAT not found in API query\n";
}

// Check TodayEarningsMovements directly
echo "\n=== TODAY EARNINGS MOVEMENTS (DIRECT) ===\n";
$stmt = $pdo->prepare("SELECT ticker, eps_actual, revenue_actual, updated_at FROM TodayEarningsMovements WHERE ticker = 'ATAT'");
$stmt->execute();
$atatDirect = $stmt->fetch(PDO::FETCH_ASSOC);

if ($atatDirect) {
    echo "Ticker: " . $atatDirect['ticker'] . "\n";
    echo "EPS Actual: " . ($atatDirect['eps_actual'] ?: 'NULL') . "\n";
    echo "Revenue Actual: " . ($atatDirect['revenue_actual'] ?: 'NULL') . "\n";
    echo "Updated: " . $atatDirect['updated_at'] . "\n";
} else {
    echo "❌ ATAT not found in TodayEarningsMovements\n";
}
?>
