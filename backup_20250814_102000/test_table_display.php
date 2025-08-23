<?php
require_once 'config.php';
require_once 'utils/database.php';

echo "=== TABLE DISPLAY TEST ===\n\n";

// Test the API function directly
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

$earnings = getEarningsDataWithMarketCap($pdo, $date);

echo "✅ Data loaded: " . count($earnings) . " records\n\n";

// Show table structure
echo "📋 TABLE STRUCTURE:\n";
echo "   Expected columns: #, Ticker, Čas reportovania, Market Cap, Size, Current Price, Price Change %, EPS Estimate, EPS Actual, Revenue Estimate, Revenue Actual\n\n";

// Show first 5 records
echo "📊 FIRST 5 RECORDS:\n";
for ($i = 0; $i < min(5, count($earnings)); $i++) {
    $item = $earnings[$i];
    echo sprintf("   %d. %-6s | %-3s | %-10s | %-5s | $%-8.2f | %-8s | %-8s | %-8s | %-8s | %-8s\n",
        $i + 1,
        $item['ticker'],
        $item['report_time'],
        $item['market_cap'] ? number_format($item['market_cap'] / 1000000000, 1) . 'B' : 'N/A',
        $item['size'] ?? 'N/A',
        $item['current_price'] ?? 0,
        $item['price_change_percent'] ? number_format($item['price_change_percent'], 2) . '%' : 'N/A',
        $item['eps_estimate'] ? '$' . number_format($item['eps_estimate'], 2) : 'N/A',
        $item['eps_actual'] ? '$' . number_format($item['eps_actual'], 2) : 'N/A',
        $item['revenue_estimate'] ? '$' . number_format($item['revenue_estimate'] / 1000000, 1) . 'M' : 'N/A',
        $item['revenue_actual'] ? '$' . number_format($item['revenue_actual'] / 1000000, 1) . 'M' : 'N/A'
    );
}

echo "\n🎯 FRONTEND TEST:\n";
echo "   Open: http://localhost/earnings-table/public/earnings-table.html\n";
echo "   Check if table header is visible\n";
echo "   Verify all columns are displayed\n";
?>
