<?php
require_once 'config.php';

echo "=== TODAY EARNINGS MOVEMENTS DATA ===\n";

// Check what data is actually in the table
$stmt = $pdo->query("SELECT ticker, updated_at FROM TodayEarningsMovements ORDER BY updated_at DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    echo "Ticker: {$row['ticker']}, Updated: {$row['updated_at']}\n";
}

echo "\n=== EARNINGS TICKERS TODAY DATA ===\n";
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

$stmt = $pdo->prepare("SELECT ticker, report_date FROM EarningsTickersToday WHERE report_date = ? ORDER BY ticker LIMIT 10");
$stmt->execute([$date]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    echo "Ticker: {$row['ticker']}, Report Date: {$row['report_date']}\n";
}
?>
