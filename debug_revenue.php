<?php
require 'config.php';

echo "=== REVENUE DATA DEBUG ===\n";

$stmt = $pdo->query("SELECT ticker, revenue_estimate, revenue_actual FROM TodayEarningsMovements WHERE revenue_estimate IS NOT NULL OR revenue_actual IS NOT NULL LIMIT 10");

while($row = $stmt->fetch()) {
    echo $row['ticker'] . ": ";
    echo "revenue_estimate=" . var_export($row['revenue_estimate'], true) . ", ";
    echo "revenue_actual=" . var_export($row['revenue_actual'], true) . "\n";
}

echo "\n=== ALL REVENUE DATA ===\n";
$stmt = $pdo->query("SELECT ticker, revenue_estimate, revenue_actual FROM TodayEarningsMovements ORDER BY revenue_estimate ASC LIMIT 10");

while($row = $stmt->fetch()) {
    echo $row['ticker'] . ": ";
    echo "revenue_estimate=" . var_export($row['revenue_estimate'], true) . ", ";
    echo "revenue_actual=" . var_export($row['revenue_actual'], true) . "\n";
}
?>
