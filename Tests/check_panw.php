<?php
require_once 'config.php';

echo "=== CHECKING PANW DATA ===\n";

try {
    // Check PANW in EarningsTickersToday
    $stmt = $pdo->prepare("SELECT ticker, eps_estimate, revenue_estimate FROM EarningsTickersToday WHERE ticker = 'PANW'");
    $stmt->execute();
    $row = $stmt->fetch();
    
    if ($row) {
        echo "PANW in EarningsTickersToday: EPS=" . ($row['eps_estimate'] ?: 'NULL') . 
             ", Rev=" . ($row['revenue_estimate'] ?: 'NULL') . "\n";
    } else {
        echo "PANW: NOT FOUND in EarningsTickersToday\n";
    }
    
    // Check PANW in TodayEarningsMovements
    $stmt = $pdo->prepare("SELECT ticker, market_cap, current_price, eps_estimate FROM TodayEarningsMovements WHERE ticker = 'PANW'");
    $stmt->execute();
    $row = $stmt->fetch();
    
    if ($row) {
        echo "PANW in TodayEarningsMovements: MC=" . ($row['market_cap'] ?: 'NULL') . 
             ", Price=" . ($row['current_price'] ?: 'NULL') . 
             ", EPS=" . ($row['eps_estimate'] ?: 'NULL') . "\n";
    } else {
        echo "PANW: NOT FOUND in TodayEarningsMovements\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
