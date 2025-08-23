<?php
require_once 'config.php';

echo "=== CHECKING DETAILED ESTIMATES ===\n";

try {
    // Check records with estimates
    $stmt = $pdo->prepare("SELECT ticker, eps_estimate, revenue_estimate FROM TodayEarningsMovements WHERE eps_estimate IS NOT NULL OR revenue_estimate IS NOT NULL LIMIT 10");
    $stmt->execute();
    
    echo "Records with estimates:\n";
    while($row = $stmt->fetch()) {
        echo $row['ticker'] . ': EPS=' . ($row['eps_estimate'] ?: 'NULL') . 
             ', Rev=' . ($row['revenue_estimate'] ?: 'NULL') . "\n";
    }
    
    // Count revenue estimates
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements WHERE revenue_estimate IS NOT NULL");
    $stmt->execute();
    $revenueCount = $stmt->fetch()['count'];
    
    echo "\nRevenue estimates count: " . $revenueCount . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
