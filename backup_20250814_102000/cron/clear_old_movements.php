<?php
/**
 * Clear Old Movements Cron
 * Removes old records from TodayEarningsMovements table
 * Should run daily before new data is inserted
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';

// Lock mechanism
$lock = new Lock('clear_old_movements');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

echo "🧹 CLEARING OLD MOVEMENTS STARTED\n";

try {
    // Use US Eastern Time to match other cron jobs
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $today = $usDate->format('Y-m-d');
    
    // Get count before clearing
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements");
    $stmt->execute();
    $beforeCount = $stmt->fetch()['count'];
    
    echo "📊 Records before clearing: " . $beforeCount . "\n";
    
    // Clear all records (since this table should only contain today's data)
    $stmt = $pdo->prepare("DELETE FROM TodayEarningsMovements");
    $stmt->execute();
    $deletedCount = $stmt->rowCount();
    
    echo "🗑️  Deleted " . $deletedCount . " old records\n";
    
    // Verify table is empty
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements");
    $stmt->execute();
    $afterCount = $stmt->fetch()['count'];
    
    echo "📊 Records after clearing: " . $afterCount . "\n";
    
    if ($afterCount == 0) {
        echo "✅ SUCCESS: Table cleared successfully\n";
    } else {
        echo "⚠️  WARNING: Table not completely cleared\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error clearing old movements: " . $e->getMessage() . "\n";
    exit(1);
}

echo "🎯 Clear old movements completed\n";
?>
