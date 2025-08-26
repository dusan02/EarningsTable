<?php
require_once 'config.php';

echo "=== CURRENT DATA ANALYSIS ===\n";

// Check today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$today = $usDate->format('Y-m-d');
echo "Today's date (NY): {$today}\n\n";

// Check EarningsTickersToday
echo "=== EARNINGS TICKERS TODAY ===\n";
$stmt = $pdo->prepare("SELECT COUNT(*) as total, MIN(report_date) as min_date, MAX(report_date) as max_date FROM earningstickerstoday");
$stmt->execute();
$summary = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total records: {$summary['total']}\n";
echo "Date range: {$summary['min_date']} to {$summary['max_date']}\n";

$stmt = $pdo->prepare("SELECT ticker, report_date FROM earningstickerstoday WHERE report_date = ? ORDER BY ticker LIMIT 5");
$stmt->execute([$today]);
$todayTickers = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Today's tickers (" . count($todayTickers) . "): ";
foreach ($todayTickers as $ticker) {
    echo $ticker['ticker'] . " ";
}
echo "\n";

// Check TodayEarningsMovements
echo "\n=== TODAY EARNINGS MOVEMENTS ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM todayearningsmovements");
$total = $stmt->fetchColumn();
echo "Total records: {$total}\n";

$stmt = $pdo->query("SELECT ticker, updated_at FROM todayearningsmovements ORDER BY updated_at DESC LIMIT 5");
$recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Most recent updates:\n";
foreach ($recent as $row) {
    echo "  {$row['ticker']}: {$row['updated_at']}\n";
}

// Check if data is fresh
echo "\n=== DATA FRESHNESS CHECK ===\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM earningstickerstoday WHERE report_date = ?");
$stmt->execute([$today]);
$todayCount = $stmt->fetchColumn();

if ($todayCount > 0) {
    echo "✅ Today's earnings data: {$todayCount} records\n";
} else {
    echo "❌ No earnings data for today\n";
}

$stmt = $pdo->query("SELECT COUNT(*) FROM todayearningsmovements WHERE DATE(updated_at) = CURDATE()");
$todayMovements = $stmt->fetchColumn();

if ($todayMovements > 0) {
    echo "✅ Today's movements data: {$todayMovements} records\n";
} else {
    echo "❌ No movements data for today\n";
}
?>
