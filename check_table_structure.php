<?php
require_once 'config.php';

echo "=== TodayEarningsMovements Table Structure ===\n";

$stmt = $pdo->query('DESCRIBE TodayEarningsMovements');
while($row = $stmt->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== End of structure ===\n";
?>
