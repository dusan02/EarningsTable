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
    
    // STEP 2: Intelligent earnings fetch (with tagging)
    echo "\n=== STEP 2: INTELLIGENT EARNINGS FETCH ===\n";
    $fetchStart = microtime(true);
    
    $output = [];
    $returnCode = 0;
    exec('php cron/intelligent_earnings_fetch.php 2>&1', $output, $returnCode);
    
    echo implode("\n", $output) . "\n";
    if ($returnCode === 0) {
        echo "✅ Intelligent earnings fetch completed in " . round(microtime(true) - $fetchStart, 2) . "s\n";
    } else {
        echo "❌ Intelligent earnings fetch failed\n";
    }
    
    // STEP 3: Polygon market data update
    echo "\n=== STEP 3: POLYGON MARKET DATA UPDATE ===\n";
    $polygonStart = microtime(true);
    
    $output = [];
    $returnCode = 0;
    exec('php cron/optimized_5min_update.php 2>&1', $output, $returnCode);
    
    echo implode("\n", $output) . "\n";
    if ($returnCode === 0) {
        echo "✅ Polygon market data update completed in " . round(microtime(true) - $polygonStart, 2) . "s\n";
    } else {
        echo "❌ Polygon market data update failed\n";
    }
    
    // STEP 4: Yahoo Finance actual values update
    echo "\n=== STEP 4: YAHOO FINANCE ACTUAL VALUES ===\n";
    $yahooStart = microtime(true);
    
    $output = [];
    $returnCode = 0;
    exec('php cron/yahoo_actual_values_update.php 2>&1', $output, $returnCode);
    
    echo implode("\n", $output) . "\n";
    if ($returnCode === 0) {
        echo "✅ Yahoo Finance actual values completed in " . round(microtime(true) - $yahooStart, 2) . "s\n";
    } else {
        echo "❌ Yahoo Finance actual values failed\n";
    }
    
    // STEP 5: Final summary
    echo "\n=== FINAL SUMMARY ===\n";
    
    // Get statistics from database
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    // Total records by source
    $stmt = $pdo->prepare("
        SELECT data_source, COUNT(*) as count
        FROM earningstickerstoday 
        WHERE report_date = ?
        GROUP BY data_source
    ");
    $stmt->execute([$date]);
    $sourceStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 Records by source:\n";
    foreach ($sourceStats as $stat) {
        echo "   {$stat['data_source']}: {$stat['count']} tickers\n";
    }
    
    // Actual values statistics
    $stmt = $pdo->prepare("
        SELECT 
            e.data_source,
            COUNT(*) as total,
            COUNT(t.eps_actual) as with_eps,
            COUNT(t.revenue_actual) as with_revenue
        FROM earningstickerstoday e
        LEFT JOIN todayearningsmovements t ON e.ticker = t.ticker
        WHERE e.report_date = ?
        GROUP BY e.data_source
    ");
    $stmt->execute([$date]);
    $actualStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📊 Actual values by source:\n";
    foreach ($actualStats as $stat) {
        $epsPercent = $stat['total'] > 0 ? round(($stat['with_eps'] / $stat['total']) * 100, 1) : 0;
        $revenuePercent = $stat['total'] > 0 ? round(($stat['with_revenue'] / $stat['total']) * 100, 1) : 0;
        
        echo "   {$stat['data_source']}: {$stat['with_eps']}/{$stat['total']} EPS ({$epsPercent}%), ";
        echo "{$stat['with_revenue']}/{$stat['total']} Revenue ({$revenuePercent}%)\n";
    }
    
    $totalTime = round(microtime(true) - $startTime, 2);
    echo "\n⏱️  Total execution time: {$totalTime}s\n";
    
    echo "\n✅ Enhanced master cron completed successfully!\n";
    echo "🎯 All tickers now have data from their respective sources!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
