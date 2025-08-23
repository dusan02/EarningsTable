<?php
require 'config.php';

echo "=== API DATA TYPES DEBUG ===\n";

// Get data directly from database
$stmt = $pdo->query("SELECT ticker, revenue_estimate, revenue_actual FROM TodayEarningsMovements LIMIT 5");
$data = $stmt->fetchAll();

echo "Database data types:\n";
foreach($data as $row) {
    echo $row['ticker'] . ":\n";
    echo "  revenue_estimate: " . var_export($row['revenue_estimate'], true) . " (type: " . gettype($row['revenue_estimate']) . ")\n";
    echo "  revenue_actual: " . var_export($row['revenue_actual'], true) . " (type: " . gettype($row['revenue_actual']) . ")\n";
    echo "\n";
}

// Simulate JSON encoding/decoding
echo "After JSON encode/decode:\n";
$json = json_encode($data);
$decoded = json_decode($json, true);

foreach($decoded as $row) {
    echo $row['ticker'] . ":\n";
    echo "  revenue_estimate: " . var_export($row['revenue_estimate'], true) . " (type: " . gettype($row['revenue_estimate']) . ")\n";
    echo "  revenue_actual: " . var_export($row['revenue_actual'], true) . " (type: " . gettype($row['revenue_actual']) . ")\n";
    echo "\n";
}

// Test JavaScript-like behavior
echo "JavaScript-like parseFloat behavior:\n";
foreach($data as $row) {
    echo $row['ticker'] . ":\n";
    $est = $row['revenue_estimate'];
    $act = $row['revenue_actual'];
    
    echo "  revenue_estimate: " . var_export($est, true) . " -> floatval(): " . var_export(floatval($est), true) . "\n";
    echo "  revenue_actual: " . var_export($act, true) . " -> floatval(): " . var_export(floatval($act), true) . "\n";
    echo "\n";
}
?>
