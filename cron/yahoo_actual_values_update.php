<?php
/**
 * 🚀 YAHOO FINANCE ACTUAL VALUES UPDATE
 * 
 * Aktualizuje actual hodnoty pre tickery ktoré pochádzajú z Yahoo Finance
 * - Spúšťa sa každých 5 minút
 * - Aktualizuje len tickery s data_source = 'yahoo_finance'
 * - Získa actual hodnoty z Yahoo Finance API
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/error_handler.php';
require_once __DIR__ . '/../common/Lock.php';
require_once __DIR__ . '/../common/YahooFinance.php';

// Lock mechanism
$lock = new Lock('yahoo_actual_values_update');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 YAHOO FINANCE ACTUAL VALUES UPDATE STARTED\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n";
    echo "⏰ Time: " . $usDate->format('H:i:s') . " NY\n\n";
    
    // STEP 1: Get Yahoo Finance tickers from database
    echo "=== STEP 1: GETTING YAHOO FINANCE TICKERS ===\n";
    
    $stmt = $pdo->prepare("
        SELECT ticker 
        FROM earningstickerstoday 
        WHERE data_source = 'yahoo_finance' 
        AND report_date = ?
    ");
    $stmt->execute([$date]);
    $yahooTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($yahooTickers)) {
        echo "❌ No Yahoo Finance tickers found for today\n";
        exit(0);
    }
    
    echo "✅ Found " . count($yahooTickers) . " Yahoo Finance tickers\n";
    echo "Tickers: " . implode(', ', $yahooTickers) . "\n";
    
    // STEP 2: Get actual values from Yahoo Finance
    echo "\n=== STEP 2: YAHOO FINANCE ACTUAL VALUES ===\n";
    
    $yahoo = new YahooFinance();
    $actualUpdates = [];
    $epsActualCount = 0;
    $revenueActualCount = 0;
    
    foreach ($yahooTickers as $ticker) {
        echo "Processing {$ticker}...\n";
        
        try {
            // Get actual values from Yahoo Finance
            $actualData = $yahoo->getActualValues($ticker, $date);
            
            if ($actualData) {
                $epsActual = $actualData['eps_actual'] ?? null;
                $revenueActual = $actualData['revenue_actual'] ?? null;
                
                if ($epsActual !== null || $revenueActual !== null) {
                    $actualUpdates[$ticker] = [
                        'eps_actual' => $epsActual,
                        'revenue_actual' => $revenueActual
                    ];
                    
                    if ($epsActual !== null) $epsActualCount++;
                    if ($revenueActual !== null) $revenueActualCount++;
                    
                    echo "✅ {$ticker}: EPS={$epsActual}, Revenue={$revenueActual}\n";
                } else {
                    echo "⚠️  {$ticker}: No actual values yet\n";
                }
            } else {
                echo "❌ {$ticker}: Failed to get data\n";
            }
            
            // Rate limiting - be gentle with Yahoo Finance
            usleep(500000); // 0.5 second delay
            
        } catch (Exception $e) {
            echo "❌ {$ticker}: Error - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✅ Found actual values for " . count($actualUpdates) . " tickers\n";
    echo "   - EPS actual: {$epsActualCount}\n";
    echo "   - Revenue actual: {$revenueActualCount}\n";
    
    // STEP 3: Update database
    echo "\n=== STEP 3: DATABASE UPDATE ===\n";
    
    $totalUpdates = 0;
    $updateStmt = $pdo->prepare("
        UPDATE todayearningsmovements 
        SET eps_actual = ?, 
            revenue_actual = ?,
            updated_at = NOW()
        WHERE ticker = ?
    ");
    
    foreach ($actualUpdates as $ticker => $actuals) {
        $updateStmt->execute([
            $actuals['eps_actual'],
            $actuals['revenue_actual'],
            $ticker
        ]);
        
        if ($updateStmt->rowCount() > 0) {
            $totalUpdates++;
            echo "✅ Updated {$ticker} in database\n";
        }
    }
    
    // STEP 4: Final summary
    echo "\n=== FINAL SUMMARY ===\n";
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM todayearningsmovements t
        JOIN earningstickerstoday e ON t.ticker = e.ticker 
        WHERE e.data_source = 'yahoo_finance' 
        AND e.report_date = ?
    ");
    $stmt->execute([$date]);
    $totalYahooRecords = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM todayearningsmovements t
        JOIN earningstickerstoday e ON t.ticker = e.ticker 
        WHERE e.data_source = 'yahoo_finance' 
        AND e.report_date = ?
        AND t.eps_actual IS NOT NULL
    ");
    $stmt->execute([$date]);
    $withEpsActual = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM todayearningsmovements t
        JOIN earningstickerstoday e ON t.ticker = e.ticker 
        WHERE e.data_source = 'yahoo_finance' 
        AND e.report_date = ?
        AND t.revenue_actual IS NOT NULL
    ");
    $stmt->execute([$date]);
    $withRevenueActual = $stmt->fetchColumn();
    
    echo "📊 Total Yahoo Finance records: {$totalYahooRecords}\n";
    echo "📊 Records with EPS actual: {$withEpsActual}\n";
    echo "📊 Records with Revenue actual: {$withRevenueActual}\n";
    echo "📈 Records updated this run: {$totalUpdates}\n";
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "⏱️  Execution time: {$executionTime}s\n";
    
    echo "\n✅ Yahoo Finance actual values update completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
