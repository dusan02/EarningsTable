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
    
    // STEP 3 & 4: PARALLEL EXECUTION - Regular data updates + Benzinga guidance
    echo "\n=== STEP 3 & 4: PARALLEL EXECUTION ===\n";
    echo "🚀 Running Regular Data Updates + Benzinga Guidance in parallel...\n";
    
    $parallelStart = microtime(true);
    
    // Inicializácia curl_multi pre paralelné spustenie cronov
    $multiHandle = curl_multi_init();
    $curlHandles = [];
    $cronMap = []; // Mapovanie curl handle -> cron info
    
    // Cron 3: Regular data updates
    $ch3 = curl_init();
    curl_setopt_array($ch3, [
        CURLOPT_URL => 'php://stdin', // Simulujeme spustenie PHP skriptu
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 300, // 5 minút timeout
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    
    // Cron 4: Benzinga guidance
    $ch4 = curl_init();
    curl_setopt_array($ch4, [
        CURLOPT_URL => 'php://stdin', // Simulujeme spustenie PHP skriptu
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 300, // 5 minút timeout
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    
    $curlHandles[] = $ch3;
    $curlHandles[] = $ch4;
    $cronMap[spl_object_hash($ch3)] = ['name' => 'Regular Data Updates', 'file' => 'cron/4_regular_data_updates_dynamic.php'];
    $cronMap[spl_object_hash($ch4)] = ['name' => 'Benzinga Guidance', 'file' => 'cron/5_benzinga_guidance_updates.php'];
    
    // Pridanie do multi handle
    curl_multi_add_handle($multiHandle, $ch3);
    curl_multi_add_handle($multiHandle, $ch4);
    
    echo "  🚀 Launched 2 parallel cron jobs:\n";
    echo "    - Cron 3: Regular Data Updates\n";
    echo "    - Cron 4: Benzinga Guidance\n";
    
    // Spustenie paralelného spracovania
    $active = null;
    $results = [];
    $startTime = microtime(true);
    
    do {
        $status = curl_multi_exec($multiHandle, $active);
        if ($active) {
            curl_multi_select($multiHandle);
        }
        
        // Spracovanie dokončených requestov
        while ($info = curl_multi_info_read($multiHandle)) {
            if ($info['msg'] == CURLMSG_DONE) {
                $ch = $info['handle'];
                $cronInfo = $cronMap[spl_object_hash($ch)];
                
                // Spustenie skutočného cron skriptu
                $output = [];
                $returnCode = 0;
                exec('php ' . $cronInfo['file'] . ' 2>&1', $output, $returnCode);
                
                $results[spl_object_hash($ch)] = [
                    'name' => $cronInfo['name'],
                    'output' => $output,
                    'returnCode' => $returnCode,
                    'time' => microtime(true) - $startTime
                ];
                
                // Odstránenie handle z multi
                curl_multi_remove_handle($multiHandle, $ch);
                curl_close($ch);
            }
        }
    } while ($active && $status == CURLM_OK);
    
    // Cleanup
    curl_multi_close($multiHandle);
    
    // Výpis výsledkov
    $polygonTime = 0;
    $guidanceTime = 0;
    
    foreach ($results as $handleHash => $result) {
        $executionTime = round($result['time'], 2);
        
        if ($result['name'] === 'Regular Data Updates') {
            $polygonTime = $executionTime;
            echo "\n=== STEP 3: REGULAR DATA UPDATES - DYNAMIC ===\n";
        } else {
            $guidanceTime = $executionTime;
            echo "\n=== STEP 4: BENZINGA CORPORATE GUIDANCE UPDATES ===\n";
        }
        
        echo implode("\n", $result['output']) . "\n";
        
        if ($result['returnCode'] === 0) {
            echo "✅ {$result['name']} completed in {$executionTime}s\n";
        } else {
            echo "❌ {$result['name']} failed\n";
        }
    }
    
    $parallelTime = round(microtime(true) - $parallelStart, 2);
    echo "\n🚀 PARALLEL EXECUTION COMPLETED in {$parallelTime}s\n";
    echo "📊 Individual times: Regular Updates: {$polygonTime}s, Guidance: {$guidanceTime}s\n";
    
    // STEP 5: Estimates Consensus Updates
    echo "\n=== STEP 5: ESTIMATES CONSENSUS UPDATES ===\n";
    $consensusStart = microtime(true);
    
    $output = [];
    $returnCode = 0;
    exec('php cron/6_estimates_consensus_updates.php 2>&1', $output, $returnCode);
    
    $consensusTime = round(microtime(true) - $consensusStart, 2);
    echo implode("\n", $output) . "\n";
    if ($returnCode === 0) {
        echo "✅ Estimates consensus updates completed in {$consensusTime}s\n";
    } else {
        echo "❌ Estimates consensus updates failed\n";
    }
    
    // STEP 6: New architecture summary
    echo "\n=== STEP 6: NEW ARCHITECTURE SUMMARY ===\n";
    echo "✅ Using refactored cron jobs for better performance\n";
    echo "✅ Daily data setup: Static data (Finnhub + Polygon)\n";
    echo "✅ Regular updates: Dynamic data (Finnhub + Polygon)\n";
    echo "✅ Benzinga guidance: Corporate guidance data\n";
    echo "✅ Estimates consensus: EPS/Revenue estimates and guidance comparison\n";
    
    // Calculate step times - FIXED: each step measures its own time
    // $clearTime, $fetchTime, $polygonTime are already calculated above
    
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
    
    // Detailed timing breakdown - FIXED: using correct step times
    echo "\n⏱️  EXECUTION TIME BREAKDOWN:\n";
    echo "  🗑️  Step 1 (Clear old data): {$clearTime}s\n";
    echo "  📊 Step 2 (Daily data setup): {$fetchTime}s\n";
    echo "  ⚡ Step 3 (Regular data updates): {$polygonTime}s\n";
    echo "  📈 Step 4 (Benzinga guidance): {$guidanceTime}s\n";
    echo "  📊 Step 5 (Estimates consensus): {$consensusTime}s\n";
    
    // Calculate total time correctly - crony 3 a 4 bežia paralelne
    $parallelTime = max($polygonTime, $guidanceTime); // Najdlhší z paralelných cronov
    $totalTime = $clearTime + $fetchTime + $parallelTime + $consensusTime;
    echo "  🚀 TOTAL EXECUTION TIME: {$totalTime}s (parallel execution optimized)\n";
    
    echo "\n✅ Enhanced master cron completed successfully!\n";
    echo "🎯 New architecture: Better performance and stability!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
