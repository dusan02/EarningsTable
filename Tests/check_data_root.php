<?php
require_once 'config.php';

echo "=== DATABASE DATA CHECK ===\n\n";

// Check TodayEarningsMovements
$stmt = $pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements");
$movementsCount = $stmt->fetchColumn();
echo "TodayEarningsMovements records: {$movementsCount}\n";

// Check EarningsTickersToday
$stmt = $pdo->query("SELECT COUNT(*) FROM EarningsTickersToday");
$tickersCount = $stmt->fetchColumn();
echo "EarningsTickersToday records: {$tickersCount}\n";

// Show sample data from TodayEarningsMovements
if ($movementsCount > 0) {
    echo "\n=== SAMPLE DATA FROM TodayEarningsMovements ===\n";
    $stmt = $pdo->query("SELECT ticker, current_price, market_cap, price_change_percent FROM TodayEarningsMovements LIMIT 5");
    while ($row = $stmt->fetch()) {
        echo "- {$row['ticker']}: \${$row['current_price']} (MC: {$row['market_cap']}, Change: {$row['price_change_percent']}%)\n";
    }
}

// Show sample data from EarningsTickersToday
if ($tickersCount > 0) {
    echo "\n=== SAMPLE DATA FROM EarningsTickersToday ===\n";
    $stmt = $pdo->query("SELECT ticker, report_time, eps_estimate FROM EarningsTickersToday LIMIT 5");
    while ($row = $stmt->fetch()) {
        echo "- {$row['ticker']}: {$row['report_time']} (EPS est: {$row['eps_estimate']})\n";
    }
}

echo "\n=== CHECK COMPLETE ===\n";
?>
