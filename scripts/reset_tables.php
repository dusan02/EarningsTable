<?php
require_once 'config.php';
require_once __DIR__ . '/../common/error_handler.php';

echo "=== RESETTING TABLES ===\n";

try {
    // Reset TodayEarningsMovements table
    echo "Truncating TodayEarningsMovements...\n";
    $stmt = $pdo->prepare("TRUNCATE TABLE TodayEarningsMovements");
    $stmt->execute();
    echo "✅ TodayEarningsMovements table reset\n";
    
    // Reset EarningsTickersToday table  
    echo "Truncating EarningsTickersToday...\n";
    $stmt = $pdo->prepare("TRUNCATE TABLE EarningsTickersToday");
    $stmt->execute();
    echo "✅ EarningsTickersToday table reset\n";
    
    // Verify tables are empty
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements");
    $stmt->execute();
    $count1 = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM EarningsTickersToday");
    $stmt->execute();
    $count2 = $stmt->fetchColumn();
    
    echo "\n=== VERIFICATION ===\n";
    echo "TodayEarningsMovements records: {$count1}\n";
    echo "EarningsTickersToday records: {$count2}\n";
    
    if ($count1 == 0 && $count2 == 0) {
        echo "✅ Both tables are now empty and ready for testing\n";
    } else {
        echo "❌ Tables are not empty!\n";
    }
    
} catch (Exception $e) {
    logDatabaseError('reset_tables', 'TRUNCATE TABLE', [], $e->getMessage(), [
        'operation' => 'reset_tables'
    ]);
    displayError("ERROR: " . $e->getMessage());
    exit(1);
}
?>
