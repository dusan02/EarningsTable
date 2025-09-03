<?php
require_once 'config.php';

echo "=== CHECKING EPS AND REVENUE ACTUAL VALUES ===\n\n";

// Check EPS and Revenue actual values
$stmt = $pdo->prepare("SELECT ticker, eps_actual, revenue_actual FROM TodayEarningsMovements WHERE eps_actual IS NOT NULL OR revenue_actual IS NOT NULL LIMIT 10");
$stmt->execute();
$results = $stmt->fetchAll();

foreach ($results as $row) {
    echo "Ticker: {$row['ticker']}, EPS Actual: " . ($row['eps_actual'] ?? 'NULL') . ", Revenue Actual: " . ($row['revenue_actual'] ?? 'NULL') . "\n";
}

echo "\n=== TOTAL COUNT ===\n";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE eps_actual IS NOT NULL");
$stmt->execute();
$result = $stmt->fetch();
echo "Records with EPS actual: " . $result['count'] . "\n";

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE revenue_actual IS NOT NULL");
$stmt->execute();
$result = $stmt->fetch();
echo "Records with Revenue actual: " . $result['count'] . "\n";
?>
