<?php
/**
 * Update Finnhub Data Every 5 Minutes
 * Updates EPS actual and Revenue actual values
 * Runs every 5 minutes after initial setup
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';
require_once __DIR__ . '/../common/Finnhub.php';

// Lock mechanism
$lock = new Lock('finnhub_5min_update');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 FINNHUB 5-MINUTE UPDATE STARTED\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n";
    echo "⏰ Time: " . $usDate->format('H:i:s') . " NY\n\n";
    
    // STEP 1: Get earnings calendar from Finnhub
    echo "=== STEP 1: FETCHING FINNHUB EARNINGS CALENDAR ===\n";
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $earningsData = $response['earningsCalendar'] ?? [];
    
    if (empty($earningsData)) {
        echo "❌ No earnings data found for today\n";
        exit(1);
    }
    
    echo "✅ Found " . count($earningsData) . " earnings reports today\n";
    
    // STEP 2: Update actual values in database
    echo "\n=== STEP 2: UPDATING ACTUAL VALUES ===\n";
    
    $updatedCount = 0;
    $epsActualCount = 0;
    $revenueActualCount = 0;
    
    foreach ($earningsData as $earning) {
        $ticker = $earning['symbol'] ?? '';
        if (empty($ticker)) continue;
        
        $epsActual = $earning['epsActual'] ?? null;
        $revenueActual = $earning['revenueActual'] ?? null;
        
        // Only update if we have actual values
        if ($epsActual !== null || $revenueActual !== null) {
            echo "Updating {$ticker}... ";
            
            $stmt = $pdo->prepare("
                UPDATE TodayEarningsMovements 
                SET 
                    eps_actual = ?,
                    revenue_actual = ?,
                    updated_at = NOW()
                WHERE ticker = ?
            ");
            
            $stmt->execute([$epsActual, $revenueActual, $ticker]);
            
            if ($epsActual !== null) $epsActualCount++;
            if ($revenueActual !== null) $revenueActualCount++;
            $updatedCount++;
            
            echo "✅ Updated\n";
        }
    }
    
    // STEP 3: Summary
    echo "\n=== STEP 3: UPDATE SUMMARY ===\n";
    
    // Count records with actual values
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE eps_actual IS NOT NULL");
    $stmt->execute();
    $withEpsActual = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE revenue_actual IS NOT NULL");
    $stmt->execute();
    $withRevenueActual = $stmt->fetchColumn();
    
    echo "📊 Records with EPS actual: {$withEpsActual}\n";
    echo "📊 Records with Revenue actual: {$withRevenueActual}\n";
    echo "📊 Tickers updated this run: {$updatedCount}\n";
    echo "📊 EPS actual values added: {$epsActualCount}\n";
    echo "📊 Revenue actual values added: {$revenueActualCount}\n";
    
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
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "\n⏱️  Update time: {$executionTime}s\n";
    echo "✅ FINNHUB 5-MINUTE UPDATE COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
