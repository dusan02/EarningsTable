<?php
require_once dirname(__DIR__) . '/config.php';

echo "=== UPDATED MASTER CRON ===\n";

$startTime = microtime(true);

// Step 1: Run intelligent earnings fetch (replaces old earnings fetch)
echo "\n=== STEP 1: INTELLIGENT EARNINGS FETCH ===\n";
$earningsStart = microtime(true);

include dirname(__DIR__) . '/cron/intelligent_earnings_fetch.php';

$earningsTime = microtime(true) - $earningsStart;
echo "Earnings fetch completed in: " . round($earningsTime, 2) . "s\n";

// Step 2: Run 5-minute updates (if needed)
echo "\n=== STEP 2: 5-MINUTE UPDATES ===\n";
$updatesStart = microtime(true);

include dirname(__DIR__) . '/cron/optimized_5min_update.php';

$updatesTime = microtime(true) - $updatesStart;
echo "5-minute updates completed in: " . round($updatesTime, 2) . "s\n";

// Step 3: Run daily cleanup
echo "\n=== STEP 3: DAILY CLEANUP ===\n";
$cleanupStart = microtime(true);

include dirname(__DIR__) . '/cron/clear_old_data.php';

$cleanupTime = microtime(true) - $cleanupStart;
echo "Daily cleanup completed in: " . round($cleanupTime, 2) . "s\n";

// Final summary
$totalTime = microtime(true) - $startTime;
echo "\n=== FINAL SUMMARY ===\n";
echo "Total execution time: " . round($totalTime, 2) . "s\n";
echo "✅ Updated master cron completed successfully!\n";
echo "This cron now uses intelligent earnings fetch with multiple sources.\n";
?>
