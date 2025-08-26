<?php
require_once 'config.php';

echo "=== SHARES OUTSTANDING DATA ===\n";

// Check sharesoutstanding table
$stmt = $pdo->query("SELECT ticker, shares_outstanding, updated_at FROM sharesoutstanding ORDER BY updated_at DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    echo "Ticker: {$row['ticker']}, Shares: {$row['shares_outstanding']}, Updated: {$row['updated_at']}\n";
}

echo "\n=== TODAY EARNINGS MOVEMENTS - DETAILED ===\n";
$stmt = $pdo->query("SELECT ticker, current_price, market_cap, updated_at FROM TodayEarningsMovements ORDER BY updated_at DESC LIMIT 10");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    echo "Ticker: {$row['ticker']}, Price: {$row['current_price']}, Market Cap: {$row['market_cap']}, Updated: {$row['updated_at']}\n";
}

echo "\n=== EARNINGS TICKERS TODAY - DETAILED ===\n";
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

$stmt = $pdo->prepare("SELECT ticker, report_date, eps_estimate, revenue_estimate FROM EarningsTickersToday WHERE report_date = ? ORDER BY ticker LIMIT 10");
$stmt->execute([$date]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
    echo "Ticker: {$row['ticker']}, Date: {$row['report_date']}, EPS Est: {$row['eps_estimate']}, Rev Est: {$row['revenue_estimate']}\n";
}
?>
