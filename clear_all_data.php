<?php
require_once 'config.php';

echo "=== COMPLETE DATA CLEARING ===\n";
echo "This will completely clear ALL data from both tables!\n\n";

// Get current counts
$beforeMov = (int)$pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements")->fetchColumn();
$beforeEtt = (int)$pdo->query("SELECT COUNT(*) FROM EarningsTickersToday")->fetchColumn();

echo "Current data:\n";
echo "- TodayEarningsMovements: {$beforeMov} records\n";
echo "- EarningsTickersToday: {$beforeEtt} records\n\n";

// Auto-confirm for faster execution
echo "Auto-confirming deletion...\n";

echo "\n=== CLEARING DATA ===\n";

try {
    // Clear TodayEarningsMovements completely
    $pdo->exec("DELETE FROM TodayEarningsMovements");
    $afterMov = (int)$pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements")->fetchColumn();
    echo "✅ TodayEarningsMovements: {$beforeMov} → {$afterMov} records\n";
    
    // Clear EarningsTickersToday completely
    $pdo->exec("DELETE FROM EarningsTickersToday");
    $afterEtt = (int)$pdo->query("SELECT COUNT(*) FROM EarningsTickersToday")->fetchColumn();
    echo "✅ EarningsTickersToday: {$beforeEtt} → {$afterEtt} records\n";
    
    echo "\n🎯 ALL DATA CLEARED!\n";
    echo "Now you can run cron jobs to test fresh data fetching.\n";
    echo "\nSuggested next steps:\n";
    echo "1. php cron/intelligent_earnings_fetch.php\n";
    echo "2. Check dashboard: http://localhost:8080/dashboard-fixed.html\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
