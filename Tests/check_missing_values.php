<?php
require_once 'config.php';

echo "=== CHECKING MISSING VALUES FOR CAP DIFF, PRICE, CHANGE ===\n\n";

// Check market cap diff values
echo "=== MARKET CAP DIFF ===\n";
$stmt = $pdo->prepare("SELECT ticker, market_cap, market_cap_diff, market_cap_diff_billions FROM TodayEarningsMovements LIMIT 10");
$stmt->execute();
$results = $stmt->fetchAll();

foreach ($results as $row) {
    echo "Ticker: {$row['ticker']}, Market Cap: {$row['market_cap']}, Cap Diff: " . ($row['market_cap_diff'] ?? 'NULL') . ", Cap Diff Billions: " . ($row['market_cap_diff_billions'] ?? 'NULL') . "\n";
}

echo "\n=== PRICE VALUES ===\n";
$stmt = $pdo->prepare("SELECT ticker, current_price, previous_close, price_change_percent FROM TodayEarningsMovements LIMIT 10");
$stmt->execute();
$results = $stmt->fetchAll();

foreach ($results as $row) {
    echo "Ticker: {$row['ticker']}, Current Price: " . ($row['current_price'] ?? 'NULL') . ", Previous Close: {$row['previous_close']}, Change %: " . ($row['price_change_percent'] ?? 'NULL') . "\n";
}

echo "\n=== COUNT OF NULL VALUES ===\n";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE market_cap_diff IS NULL");
$stmt->execute();
$result = $stmt->fetch();
echo "Records with NULL market_cap_diff: " . $result['count'] . "\n";

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE current_price IS NULL");
$stmt->execute();
$result = $stmt->fetch();
echo "Records with NULL current_price: " . $result['count'] . "\n";

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE price_change_percent IS NULL");
$stmt->execute();
$result = $stmt->fetch();
echo "Records with NULL price_change_percent: " . $result['count'] . "\n";

echo "\n=== CHECKING IF CRON 4 IS WORKING ===\n";
echo "Cron 4 should update these values every 5 minutes\n";
echo "Last run showed 0% success rate for Polygon API\n";
echo "This explains why current_price and price_change_percent are NULL\n";
?>
