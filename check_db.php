<?php
require_once 'config.php';

echo "=== DATABASE STATUS ===\n";
echo "TodayEarningsMovements: " . $pdo->query('SELECT COUNT(*) FROM TodayEarningsMovements')->fetchColumn() . "\n";
echo "EarningsTickersToday: " . $pdo->query('SELECT COUNT(*) FROM EarningsTickersToday')->fetchColumn() . "\n";

// Check today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');
echo "Today's date (NY): " . $date . "\n";

// Check today's tickers
$stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
$stmt->execute([$date]);
$todayTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Today's tickers: " . count($todayTickers) . "\n";
if (!empty($todayTickers)) {
    echo "Sample tickers: " . implode(', ', array_slice($todayTickers, 0, 5)) . "\n";
}
?>
