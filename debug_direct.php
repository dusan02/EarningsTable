<?php
require 'config.php';

echo "=== DIRECT DATABASE DEBUG ===\n";

// Get data directly from database
$stmt = $pdo->query("SELECT ticker, revenue_estimate, revenue_actual FROM TodayEarningsMovements ORDER BY revenue_estimate ASC LIMIT 15");

echo "First 15 records ordered by revenue_estimate ASC:\n";
while($row = $stmt->fetch()) {
    echo $row['ticker'] . ": ";
    echo "revenue_estimate=" . var_export($row['revenue_estimate'], true) . ", ";
    echo "revenue_actual=" . var_export($row['revenue_actual'], true) . "\n";
}

echo "\n=== SORTING SIMULATION ===\n";
$stmt = $pdo->query("SELECT ticker, revenue_estimate, revenue_actual FROM TodayEarningsMovements");
$data = $stmt->fetchAll();

// Simulate JavaScript sorting
usort($data, function($a, $b) {
    $aVal = floatval($a['revenue_estimate']);
    $bVal = floatval($b['revenue_estimate']);
    
    // Handle null/undefined values for revenue estimates
    if (is_nan($aVal) && is_nan($bVal)) return 0;
    if (is_nan($aVal)) return 1; // null values go to end
    if (is_nan($bVal)) return -1; // null values go to end
    
    return $aVal > $bVal ? 1 : ($aVal < $bVal ? -1 : 0);
});

echo "After sorting (ASC):\n";
for($i = 0; $i < 15; $i++) {
    $row = $data[$i];
    echo $row['ticker'] . ": ";
    echo "revenue_estimate=" . var_export($row['revenue_estimate'], true) . ", ";
    echo "revenue_actual=" . var_export($row['revenue_actual'], true) . "\n";
}
?>
