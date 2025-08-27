<?php
require_once 'config.php';

echo "=== ALL TABLES STATUS ===\n";

// Get all tables
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
    $count = $stmt->fetchColumn();
    echo "Table: {$table} - {$count} records\n";
}

echo "\n=== SHARES OUTSTANDING DATA ===\n";
echo "✅ Tabulka sharesoutstanding bola vymazaná - už sa nepoužíva\n";

echo "\n=== TODAY'S EARNINGS TICKERS ===\n";
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$today = $usDate->format('Y-m-d');

$stmt = $pdo->prepare("SELECT ticker FROM earningstickerstoday WHERE report_date = ? ORDER BY ticker LIMIT 10");
$stmt->execute([$today]);
$tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "Today's tickers: " . implode(', ', $tickers) . "\n";
?>
