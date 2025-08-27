<?php
require_once 'config.php';

echo "=== DATABASE TABLE STRUCTURE ===\n\n";

// Check TodayEarningsMovements table
echo "TodayEarningsMovements table structure:\n";
echo "--------------------------------------\n";
$stmt = $pdo->query("DESCRIBE TodayEarningsMovements");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\nEarningsTickersToday table structure:\n";
echo "-------------------------------------\n";
$stmt = $pdo->query("DESCRIBE EarningsTickersToday");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
