<?php
require_once 'config.php';

echo "=== CHECKING EPS/REVENUE ESTIMATES ===\n";

try {
    // Check EarningsTickersToday table
    $stmt = $pdo->prepare("SELECT ticker, eps_estimate, revenue_estimate FROM EarningsTickersToday LIMIT 5");
    $stmt->execute();
    
    echo "First 5 records from EarningsTickersToday:\n";
    while($row = $stmt->fetch()) {
        echo $row['ticker'] . ': EPS=' . ($row['eps_estimate'] ?: 'NULL') . 
             ', Rev=' . ($row['revenue_estimate'] ?: 'NULL') . "\n";
    }
    
    // Count records with estimates
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN eps_estimate IS NOT NULL THEN 1 END) as with_eps,
        COUNT(CASE WHEN revenue_estimate IS NOT NULL THEN 1 END) as with_revenue
        FROM EarningsTickersToday");
    $stmt->execute();
    $stats = $stmt->fetch();
    
    echo "\nEarningsTickersToday statistics:\n";
    echo "Total records: " . $stats['total'] . "\n";
    echo "With EPS estimate: " . $stats['with_eps'] . "\n";
    echo "With revenue estimate: " . $stats['with_revenue'] . "\n";
    
    // Check TodayEarningsMovements table
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total,
        COUNT(CASE WHEN eps_estimate IS NOT NULL THEN 1 END) as with_eps,
        COUNT(CASE WHEN revenue_estimate IS NOT NULL THEN 1 END) as with_revenue
        FROM TodayEarningsMovements");
    $stmt->execute();
    $stats = $stmt->fetch();
    
    echo "\nTodayEarningsMovements statistics:\n";
    echo "Total records: " . $stats['total'] . "\n";
    echo "With EPS estimate: " . $stats['with_eps'] . "\n";
    echo "With revenue estimate: " . $stats['with_revenue'] . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
