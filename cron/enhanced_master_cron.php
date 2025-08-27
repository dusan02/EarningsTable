<?php
/**
 * 🚀 ENHANCED MASTER CRON - WITH DATA SOURCE TAGGING
 * 
 * Orchestrácia všetkých cronov s novým tagging systémom:
 * 1. Clear old data
 * 2. Finnhub primary fetch (with tagging)
 * 3. Yahoo Finance secondary fetch (with tagging)
 * 4. Polygon market data (5min)
 * 5. Finnhub actual values (5min) - only Finnhub tickers
 * 6. Yahoo Finance actual values (5min) - only Yahoo tickers
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/error_handler.php';

$startTime = microtime(true);
echo "🚀 ENHANCED MASTER CRON STARTED\n";
echo "📅 Date: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // STEP 1: Clear old data
    echo "=== STEP 1: CLEARING OLD DATA ===\n";
    $clearStart = microtime(true);
    
    $output = [];
    $returnCode = 0;
    exec('php cron/clear_old_data.php --force 2>&1', $output, $returnCode);
    
    echo implode("\n", $output) . "\n";
    if ($returnCode === 0) {
        echo "✅ Clear old data completed in " . round(microtime(true) - $clearStart, 2) . "s\n";
    } else {
        echo "❌ Clear old data failed\n";
    }
    
    // STEP 2: Daily data setup - static (NEW REFACTORED VERSION)
    echo "\n=== STEP 2: DAILY DATA SETUP - STATIC ===\n";
    $fetchStart = microtime(true);
    
    $output = [];
    $returnCode = 0;
    exec('php cron/daily_data_setup_static.php 2>&1', $output, $returnCode);
    
    echo implode("\n", $output) . "\n";
    if ($returnCode === 0) {
        echo "✅ Daily data setup completed in " . round(microtime(true) - $fetchStart, 2) . "s\n";
    } else {
        echo "❌ Daily data setup failed\n";
    }
    
    // STEP 3: Regular data updates - dynamic (NEW REFACTORED VERSION)
    echo "\n=== STEP 3: REGULAR DATA UPDATES - DYNAMIC ===\n";
    $polygonStart = microtime(true);
    
    $output = [];
    $returnCode = 0;
    exec('php cron/regular_data_updates_dynamic.php 2>&1', $output, $returnCode);
    
    echo implode("\n", $output) . "\n";
    if ($returnCode === 0) {
        echo "✅ Regular data updates completed in " . round(microtime(true) - $polygonStart, 2) . "s\n";
    } else {
        echo "❌ Regular data updates failed\n";
    }
    
    // STEP 4: New architecture summary
    echo "\n=== STEP 4: NEW ARCHITECTURE SUMMARY ===\n";
    echo "✅ Using refactored cron jobs for better performance\n";
    echo "✅ Daily data setup: Static data (Finnhub + Polygon)\n";
    echo "✅ Regular updates: Dynamic data (Finnhub + Polygon)\n";
    
    // Calculate step times
    $clearTime = round(microtime(true) - $clearStart, 2);
    $fetchTime = round(microtime(true) - $fetchStart, 2);
    $polygonTime = round(microtime(true) - $polygonStart, 2);
    
    // STEP 5: Final summary with detailed timing
    echo "\n=== FINAL SUMMARY ===\n";
    
    // Get statistics from database
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    // Total records (all from Finnhub now)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM earningstickerstoday 
        WHERE report_date = ?
    ");
    $stmt->execute([$date]);
    $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "📊 Total records: {$totalCount} tickers (all from Finnhub)\n";
    
    // Actual values statistics (all from Finnhub)
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(t.eps_actual) as with_eps,
            COUNT(t.revenue_actual) as with_revenue
        FROM earningstickerstoday e
        LEFT JOIN todayearningsmovements t ON e.ticker = t.ticker
        WHERE e.report_date = ?
    ");
    $stmt->execute([$date]);
    $actualStats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $epsPercent = $actualStats['total'] > 0 ? round(($actualStats['with_eps'] / $actualStats['total']) * 100, 1) : 0;
    $revenuePercent = $actualStats['total'] > 0 ? round(($actualStats['with_revenue'] / $actualStats['total']) * 100, 1) : 0;
    
    echo "\n📊 Actual values: {$actualStats['with_eps']}/{$actualStats['total']} EPS ({$epsPercent}%), ";
    echo "{$actualStats['with_revenue']}/{$actualStats['total']} Revenue ({$revenuePercent}%)\n";
    
    // Detailed timing breakdown
    echo "\n⏱️  EXECUTION TIME BREAKDOWN:\n";
    echo "  🗑️  Step 1 (Clear old data): {$clearTime}s\n";
    echo "  📊 Step 2 (Daily data setup): {$fetchTime}s\n";
    echo "  ⚡ Step 3 (Regular data updates): {$polygonTime}s\n";
    echo "  📈 Step 4 (Summary): " . round(microtime(true) - $polygonStart, 2) . "s\n";
    
    $totalTime = round(microtime(true) - $startTime, 2);
    echo "  🚀 TOTAL EXECUTION TIME: {$totalTime}s\n";
    
    echo "\n✅ Enhanced master cron completed successfully!\n";
    echo "🎯 New architecture: Better performance and stability!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
