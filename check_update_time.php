<?php
require_once 'config.php';

try {
    $result = $pdo->query('SELECT updated_at FROM TodayEarningsMovements ORDER BY updated_at DESC LIMIT 5');
    echo "=== LAST UPDATE TIMES ===\n";
    while($row = $result->fetch()) {
        echo $row['updated_at'] . "\n";
    }
    
    echo "\n=== SAMPLE TICKERS ===\n";
    $result = $pdo->query('SELECT ticker, updated_at FROM TodayEarningsMovements ORDER BY updated_at DESC LIMIT 10');
    while($row = $result->fetch()) {
        echo $row['ticker'] . " - " . $row['updated_at'] . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
