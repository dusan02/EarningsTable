<?php
require_once 'config.php';
require_once 'utils/database.php';

try {
    $result = $pdo->query('SELECT COUNT(*) as count FROM TodayEarningsMovements');
    $row = $result->fetch();
    
    echo "TodayEarningsMovements count: " . $row['count'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
