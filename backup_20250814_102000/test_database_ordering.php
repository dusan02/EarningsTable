<?php
require_once __DIR__ . '/config.php';

echo "Testing database ordering...\n\n";

// Get current date in US Eastern Time
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: $date\n\n";

// Check if we have data for today
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM EarningsTickersToday WHERE report_date = ?");
$stmt->execute([$date]);
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "EarningsTickersToday count for today: $count\n";

if ($count == 0) {
    echo "No data for today. Checking recent dates...\n";
    $stmt = $pdo->query("SELECT report_date, COUNT(*) as count FROM EarningsTickersToday GROUP BY report_date ORDER BY report_date DESC LIMIT 5");
    $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($dates as $dateRow) {
        echo "  {$dateRow['report_date']}: {$dateRow['count']} records\n";
    }
    exit;
}

// Test the current query
$stmt = $pdo->prepare("
    SELECT 
        e.ticker,
        m.market_cap,
        m.current_price,
        m.size
    FROM EarningsTickersToday e
    LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
    WHERE e.report_date = ?
    ORDER BY COALESCE(m.market_cap, 0) DESC, e.ticker
    LIMIT 15
");

$stmt->execute([$date]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Current ordering (first 15 rows):\n";
echo str_repeat("-", 60) . "\n";
printf("%-8s %-15s %-10s %-8s\n", "Ticker", "Market Cap", "Price", "Size");
echo str_repeat("-", 60) . "\n";

foreach($data as $row) {
    $marketCap = $row['market_cap'] ? number_format($row['market_cap'] / 1e9, 2) . 'B' : 'NULL';
    $price = $row['current_price'] ? '$' . number_format($row['current_price'], 2) : 'NULL';
    printf("%-8s %-15s %-10s %-8s\n", 
           $row['ticker'], 
           $marketCap, 
           $price, 
           $row['size'] ?? 'NULL');
}

echo "\n";
?>
