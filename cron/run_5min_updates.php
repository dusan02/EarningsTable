<?php
/**
 * Run 5-Minute Updates
 * Executes both Polygon and Finnhub 5-minute updates
 * Can be scheduled to run every 5 minutes
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';

// Lock mechanism
$lock = new Lock('run_5min_updates');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 5-MINUTE UPDATES STARTED\n";

try {
    // Get current time
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    
    echo "📅 Date: " . $usDate->format('Y-m-d') . "\n";
    echo "⏰ Time: " . $usDate->format('H:i:s') . " NY\n\n";
    
    // STEP 1: Run Polygon updates
    echo "=== STEP 1: POLYGON UPDATES ===\n";
    $polygonScript = __DIR__ . '/update_polygon_data_5min.php';
    if (file_exists($polygonScript)) {
        echo "Running Polygon 5-minute update...\n";
        $output = shell_exec("D:\\xampp\\php\\php.exe \"{$polygonScript}\" 2>&1");
        echo $output;
    } else {
        echo "❌ Polygon 5-minute script not found\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // STEP 2: Run Finnhub updates
    echo "=== STEP 2: FINNHUB UPDATES ===\n";
    $finnhubScript = __DIR__ . '/update_finnhub_data_5min.php';
    if (file_exists($finnhubScript)) {
        echo "Running Finnhub 5-minute update...\n";
        $output = shell_exec("D:\\xampp\\php\\php.exe \"{$finnhubScript}\" 2>&1");
        echo $output;
    } else {
        echo "❌ Finnhub 5-minute script not found\n";
    }
    
    // STEP 3: Final summary
    echo "\n=== FINAL SUMMARY ===\n";
    
    // Count all data types
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE current_price > 0");
    $stmt->execute();
    $withPrices = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE market_cap > 0");
    $stmt->execute();
    $withMarketCap = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE eps_actual IS NOT NULL");
    $stmt->execute();
    $withEpsActual = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE revenue_actual IS NOT NULL");
    $stmt->execute();
    $withRevenueActual = $stmt->fetchColumn();
    
    echo "📊 Total records with prices: {$withPrices}\n";
    echo "📊 Total records with market cap: {$withMarketCap}\n";
    echo "📊 Total records with EPS actual: {$withEpsActual}\n";
    echo "📊 Total records with Revenue actual: {$withRevenueActual}\n";
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "\n⏱️  Total execution time: {$executionTime}s\n";
    echo "✅ 5-MINUTE UPDATES COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
