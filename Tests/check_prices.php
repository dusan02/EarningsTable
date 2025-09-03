<?php
require_once 'config.php';

echo "=== CHECKING CURRENT PRICES ===\n\n";

// Check current prices
$stmt = $pdo->prepare("SELECT ticker, current_price, previous_close FROM TodayEarningsMovements LIMIT 10");
$stmt->execute();
$results = $stmt->fetchAll();

foreach ($results as $row) {
    echo "Ticker: {$row['ticker']}, Current Price: " . ($row['current_price'] ?? 'NULL') . ", Previous Close: {$row['previous_close']}\n";
}

echo "\n=== CHECKING IF CRON 4 IS RUNNING ===\n";
echo "Cron 4 should update current prices every 5 minutes\n";
echo "Check if cron jobs are running:\n";
echo "1. cron/1_enhanced_master_cron.php\n";
echo "2. cron/4_regular_data_updates_dynamic.php\n";

echo "\n=== MANUAL TEST ===\n";
echo "You can manually run cron 4 to update prices:\n";
echo "php cron/4_regular_data_updates_dynamic.php\n";
?>
