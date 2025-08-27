<?php
require_once 'config.php';

echo "=== TODAYEARNINGSMOVEMENTS STATUS ===\n";
echo "Today: " . date('Y-m-d') . "\n";

$stmt = $pdo->query('SELECT COUNT(*) as total FROM todayearningsmovements WHERE DATE(updated_at) = CURDATE()');
$row = $stmt->fetch();
echo "Today records: " . $row['total'] . "\n";

$stmt = $pdo->query('SELECT COUNT(*) as total FROM todayearningsmovements');
$row = $stmt->fetch();
echo "Total records: " . $row['total'] . "\n";

echo "\n=== RECENT UPDATES ===\n";
$stmt = $pdo->query('SELECT ticker, updated_at FROM todayearningsmovements ORDER BY updated_at DESC LIMIT 5');
while ($row = $stmt->fetch()) {
    echo $row['ticker'] . " - " . $row['updated_at'] . "\n";
}

echo "\n=== DATA SOURCES ===\n";
echo "This table gets data from:\n";
echo "1. POLYGON API - Market data (prices, market cap, company names)\n";
echo "2. EPS/Revenue data can come from multiple sources\n";
echo "3. Updated via cron/intelligent_earnings_fetch.php\n";
?>
