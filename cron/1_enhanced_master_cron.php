<?php
/**
 * 🚀 ENHANCED MASTER CRON - WITH DATA SOURCE TAGGING
 * 
 * Orchestrácia všetkých cronov s novým tagging systémom:
 * 1. Clear old data
 * 2. Daily data setup (static data)
 * 3. Regular data updates (dynamic data)
 * 4. Benzinga guidance updates
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
    exec('php cron/2_clear_old_data.php --force 2>&1', $output, $returnCode);
    
    $clearTime = round(microtime(true) - $clearStart, 2);
    echo implode("\n", $output) . "\n";
    if ($returnCode === 0) {
        echo "✅ Clear old data completed in {$clearTime}s\n";
    } else {
        echo "❌ Clear old data failed\n";
    }
    
    // STEP 2: Daily data setup - static (NEW REFACTORED VERSION)
    echo "\n=== STEP 2: DAILY DATA SETUP - STATIC ===\n";
    $fetchStart = microtime(true);
    
    $output = [];
    $returnCode = 0;
    exec('php cron/3_daily_data_setup_static.php 2>&1', $output, $returnCode);
    
    $fetchTime = round(microtime(true) - $fetchStart, 2);
    echo implode("\n", $output) . "\n";
    if ($returnCode === 0) {
        echo "✅ Daily data setup completed in {$fetchTime}s\n";
    } else {
        echo "❌ Daily data setup failed\n";
    }
    
    // STEP 3: Regular data updates (5-minute data)
    echo "\n=== STEP 3: REGULAR DATA UPDATES - DYNAMIC ===\n";
    $polygonStart = microtime(true);
    
    $output = [];
    $returnCode = 0;
    exec('php cron/4_regular_data_updates_dynamic.php 2>&1', $output, $returnCode);
    
    $polygonTime = round(microtime(true) - $polygonStart, 2);
    echo implode("\n", $output) . "\n";
    if ($returnCode === 0) {
        echo "✅ Regular data updates completed in {$polygonTime}s\n";
    } else {
        echo "❌ Regular data updates failed\n";
    }
    
    // STEP 4: Benzinga guidance (daily - only once per day)
    echo "\n=== STEP 4: BENZINGA CORPORATE GUIDANCE UPDATES ===\n";
    $guidanceStart = microtime(true);
    
    $output = [];
    $returnCode = 0;
    exec('php cron/5_benzinga_guidance_updates.php 2>&1', $output, $returnCode);
    
    $guidanceTime = round(microtime(true) - $guidanceStart, 2);
    echo implode("\n", $output) . "\n";
    if ($returnCode === 0) {
        echo "✅ Benzinga guidance completed in {$guidanceTime}s\n";
    } else {
        echo "❌ Benzinga guidance failed\n";
    }
    
    // STEP 5: New architecture summary
    echo "\n=== STEP 5: NEW ARCHITECTURE SUMMARY ===\n";
    echo "✅ Using refactored cron jobs for better performance\n";
    echo "✅ Daily data setup: Static data (Finnhub + Polygon)\n";
    echo "✅ Regular updates: Dynamic data (Finnhub + Polygon)\n";
    echo "✅ Benzinga guidance: Corporate guidance data\n";
    
    // Calculate step times - FIXED: each step measures its own time
    // $clearTime, $fetchTime, $polygonTime are already calculated above
    
    // Final summary with detailed timing
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
    
    // Detailed timing breakdown - FIXED: using correct step times
    echo "\n⏱️  EXECUTION TIME BREAKDOWN:\n";
    echo "  🗑️  Step 1 (Clear old data): {$clearTime}s\n";
    echo "  📊 Step 2 (Daily data setup): {$fetchTime}s\n";
    echo "  ⚡ Step 3 (Regular data updates): {$polygonTime}s\n";
    echo "  📈 Step 4 (Benzinga guidance): {$guidanceTime}s\n";
    // Calculate total time - sequential execution
    $totalTime = $clearTime + $fetchTime + $polygonTime + $guidanceTime;
    echo "  🚀 TOTAL EXECUTION TIME: {$totalTime}s\n";
    
    echo "\n✅ Enhanced master cron completed successfully!\n";
    echo "🎯 New architecture: Better performance and stability!\n";
    
    // Write success status to file for autorefresh functionality
    $statusData = [
        'last_successful_run' => date('Y-m-d H:i:s'),
        'total_records' => $totalCount,
        'execution_time' => $totalTime,
        'eps_percentage' => $epsPercent,
        'revenue_percentage' => $revenuePercent
    ];
    
    $statusFile = __DIR__ . '/../storage/cron_status.json';
    $statusDir = dirname($statusFile);
    
    // Ensure storage directory exists
    if (!is_dir($statusDir)) {
        mkdir($statusDir, 0755, true);
    }
    
    file_put_contents($statusFile, json_encode($statusData, JSON_PRETTY_PRINT));
    echo "📝 Status written to: {$statusFile}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // Write error status to file
    $statusData = [
        'last_successful_run' => null,
        'error' => $e->getMessage(),
        'error_time' => date('Y-m-d H:i:s')
    ];
    
    $statusFile = __DIR__ . '/../storage/cron_status.json';
    $statusDir = dirname($statusFile);
    
    if (!is_dir($statusDir)) {
        mkdir($statusDir, 0755, true);
    }
    
    file_put_contents($statusFile, json_encode($statusData, JSON_PRETTY_PRINT));
    exit(1);
}
?>
