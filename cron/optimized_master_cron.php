<?php
/**
 * 🚀 OPTIMIZED MASTER CRON - BATCH PROCESSING
 * 
 * Hlavný optimalizovaný cron súbor, ktorý spúšťa všetky optimalizované cron joby
 * - Optimalizované získavanie dát
 * - Hromadné API volania
 * - Minimalizácia času vykonávania
 * - Lepšie logovanie a monitoring
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';

// Lock mechanism
$lock = new Lock('optimized_master_cron');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 OPTIMIZED MASTER CRON STARTED\n";

try {
    // Get current time
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    
    echo "📅 Date: " . $usDate->format('Y-m-d') . "\n";
    echo "⏰ Time: " . $usDate->format('H:i:s') . " NY\n\n";
    
    $totalApiCalls = 0;
    $totalExecutionTime = 0;
    $results = [];
    
    // STEP 1: Daily cleanup (if needed)
    echo "=== STEP 1: DAILY CLEANUP ===\n";
    
    $cleanupScript = __DIR__ . '/clear_old_data.php';
    if (file_exists($cleanupScript)) {
        echo "Running daily cleanup...\n";
        $cleanupStart = microtime(true);
        $output = shell_exec("C:\\tools\\php84\\php.exe \"{$cleanupScript}\" --force 2>&1");
        $cleanupTime = round(microtime(true) - $cleanupStart, 2);
        echo $output;
        echo "⏱️  Cleanup time: {$cleanupTime}s\n";
        $totalExecutionTime += $cleanupTime;
    } else {
        echo "❌ Cleanup script not found\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // STEP 2: Optimized earnings fetch (main data collection)
    echo "=== STEP 2: OPTIMIZED EARNINGS FETCH ===\n";
    
    $earningsScript = __DIR__ . '/optimized_earnings_fetch.php';
    if (file_exists($earningsScript)) {
        echo "Running optimized earnings fetch...\n";
        $earningsStart = microtime(true);
        $output = shell_exec("C:\\tools\\php84\\php.exe \"{$earningsScript}\" 2>&1");
        $earningsTime = round(microtime(true) - $earningsStart, 2);
        echo $output;
        echo "⏱️  Earnings fetch time: {$earningsTime}s\n";
        $totalExecutionTime += $earningsTime;
        
        // Extract API calls from output
        if (preg_match('/Total API calls: (\d+)/', $output, $matches)) {
            $totalApiCalls += (int)$matches[1];
        }
        
        $results['earnings_fetch'] = [
            'time' => $earningsTime,
            'success' => strpos($output, 'COMPLETED') !== false
        ];
    } else {
        echo "❌ Optimized earnings script not found\n";
        $results['earnings_fetch'] = ['time' => 0, 'success' => false];
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
    
    // STEP 3: Optimized 5-minute update (actual values and prices)
    echo "=== STEP 3: OPTIMIZED 5-MINUTE UPDATE ===\n";
    
    $updateScript = __DIR__ . '/optimized_5min_update.php';
    if (file_exists($updateScript)) {
        echo "Running optimized 5-minute update...\n";
        $updateStart = microtime(true);
        $output = shell_exec("C:\\tools\\php84\\php.exe \"{$updateScript}\" 2>&1");
        $updateTime = round(microtime(true) - $updateStart, 2);
        echo $output;
        echo "⏱️  5-minute update time: {$updateTime}s\n";
        $totalExecutionTime += $updateTime;
        
        // Extract API calls from output
        if (preg_match('/Total API calls: (\d+)/', $output, $matches)) {
            $totalApiCalls += (int)$matches[1];
        }
        
        $results['five_min_update'] = [
            'time' => $updateTime,
            'success' => strpos($output, 'COMPLETED') !== false
        ];
    } else {
        echo "❌ Optimized 5-minute update script not found\n";
        $results['five_min_update'] = ['time' => 0, 'success' => false];
    }
    
    // STEP 4: Final summary and statistics
    echo "\n=== FINAL SUMMARY ===\n";
    
    // Get database statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements");
    $stmt->execute();
    $totalRecords = $stmt->fetchColumn();
    
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
    
    echo "📊 DATABASE STATISTICS:\n";
    echo "   Total records: {$totalRecords}\n";
    echo "   Records with prices: {$withPrices}\n";
    echo "   Records with market cap: {$withMarketCap}\n";
    echo "   Records with EPS actual: {$withEpsActual}\n";
    echo "   Records with Revenue actual: {$withRevenueActual}\n";
    
    echo "\n🚀 PERFORMANCE STATISTICS:\n";
    echo "   Total API calls: {$totalApiCalls}\n";
    echo "   Total execution time: {$totalExecutionTime}s\n";
    
    echo "\n✅ STEP RESULTS:\n";
    foreach ($results as $step => $result) {
        $status = $result['success'] ? '✅' : '❌';
        echo "   {$status} {$step}: {$result['time']}s\n";
    }
    
    // Calculate efficiency metrics
    $efficiency = $totalRecords > 0 ? round($totalApiCalls / $totalRecords, 2) : 0;
    $speed = $totalRecords > 0 ? round($totalExecutionTime / $totalRecords, 3) : 0;
    
    echo "\n📈 EFFICIENCY METRICS:\n";
    echo "   API calls per record: {$efficiency}\n";
    echo "   Seconds per record: {$speed}s\n";
    
    // Show recent actual values
    echo "\n=== RECENT ACTUAL VALUES ===\n";
    $stmt = $pdo->prepare("
        SELECT ticker, eps_actual, revenue_actual, updated_at
        FROM TodayEarningsMovements 
        WHERE (eps_actual IS NOT NULL OR revenue_actual IS NOT NULL)
        ORDER BY updated_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentActuals = $stmt->fetchAll();
    
    if (!empty($recentActuals)) {
        foreach ($recentActuals as $actual) {
            $epsActual = $actual['eps_actual'] ?? 'N/A';
            $revenueActual = $actual['revenue_actual'] ?? 'N/A';
            $updatedTime = date('H:i:s', strtotime($actual['updated_at']));
            
            if ($revenueActual !== 'N/A') {
                $revenueActual = '$' . number_format($revenueActual / 1000000, 1) . 'M';
            }
            
            echo sprintf("%-6s | EPS: %-8s | Revenue: %-10s | %s\n",
                $actual['ticker'],
                $epsActual,
                $revenueActual,
                $updatedTime
            );
        }
    } else {
        echo "No actual values found yet\n";
    }
    
    $finalExecutionTime = round(microtime(true) - $startTime, 2);
    echo "\n⏱️  Total master cron time: {$finalExecutionTime}s\n";
    echo "✅ OPTIMIZED MASTER CRON COMPLETED\n";
    
    // Log the execution
    $logFile = __DIR__ . '/../logs/master_cron.log';
    $logEntry = sprintf(
        "[%s] Master cron completed - Records: %d, API calls: %d, Time: %ss\n",
        date('Y-m-d H:i:s'),
        $totalRecords,
        $totalApiCalls,
        $finalExecutionTime
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
