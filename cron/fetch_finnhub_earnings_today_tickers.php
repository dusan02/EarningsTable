<?php
/**
 * Yahoo Finance Tickers Fetch
 * Fetch tickers that report earnings today from Finnhub and save to database
 * Uses Finnhub for earnings calendar and estimates - BATCH PROCESSING
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';
require_once __DIR__ . '/../common/Finnhub.php';

// Lock mechanism
$lock = new Lock('yahoo_tickers_fetch');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 YAHOO FINANCE TICKERS FETCH STARTED\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n\n";
    
    // STEP 1: Get earnings calendar from Finnhub (ONE API CALL)
    echo "=== STEP 1: FETCHING EARNINGS CALENDAR FROM FINNHUB ===\n";
    
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $earningsData = $response['earningsCalendar'] ?? [];
    
    if (empty($earningsData)) {
        echo "❌ No earnings data found for today\n";
        exit(1);
    }
    
    echo "✅ Found " . count($earningsData) . " earnings reports today\n\n";
    
    // STEP 2: Process earnings data (NO ADDITIONAL API CALLS)
    echo "=== STEP 2: PROCESSING EARNINGS DATA ===\n";
    
    $earningsTickers = [];
    $totalProcessed = 0;
    
    foreach ($earningsData as $earning) {
        $ticker = $earning['symbol'] ?? '';
        if (empty($ticker)) continue;
        
        echo "Processing {$ticker}... ";
        
        // Extract data from Finnhub response (NO API CALL)
        $epsEstimate = $earning['epsEstimate'] ?? null;
        $epsActual = $earning['epsActual'] ?? null;
        $revenueEstimate = $earning['revenueEstimate'] ?? null;
        $revenueActual = $earning['revenueActual'] ?? null;
        
        // Finnhub doesn't provide hour info, so we'll use a simple logic
        // For now, set all to TNS (Time Not Specified)
        $reportTime = 'TNS';
        
        $quarter = $earning['quarter'] ?? null;
        $year = $earning['year'] ?? null;
        
        // Use ticker as company name (NO API CALL for company name)
        $companyName = $ticker;
        
        $earningsTickers[] = [
            'ticker' => $ticker,
            'company_name' => $companyName,
            'eps_estimate' => $epsEstimate,
            'eps_actual' => $epsActual,
            'revenue_estimate' => $revenueEstimate,
            'revenue_actual' => $revenueActual,
            'report_time' => $reportTime,
            'quarter' => $quarter,
            'year' => $year,
            'report_date' => $date
        ];
        
        echo "✅ Processed\n";
        $totalProcessed++;
    }
    
    echo "\n=== STEP 3: SAVING TO DATABASE ===\n";
    
    if (!empty($earningsTickers)) {
        // Save to EarningsTickersToday
        $stmt = $pdo->prepare("
            INSERT INTO EarningsTickersToday (
                ticker, eps_estimate, revenue_estimate, report_date, report_time
            ) VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                eps_estimate = VALUES(eps_estimate),
                revenue_estimate = VALUES(revenue_estimate),
                report_time = VALUES(report_time)
        ");
        
        $savedCount = 0;
        foreach ($earningsTickers as $tickerData) {
            $stmt->execute([
                $tickerData['ticker'],
                $tickerData['eps_estimate'],
                $tickerData['revenue_estimate'],
                $tickerData['report_date'],
                $tickerData['report_time']
            ]);
            $savedCount++;
        }
        
        echo "✅ Saved {$savedCount} tickers to EarningsTickersToday\n";
        
        // Also create entries in TodayEarningsMovements
        $stmt = $pdo->prepare("
            INSERT INTO TodayEarningsMovements (
                ticker, company_name, eps_estimate, eps_actual, revenue_estimate, revenue_actual, report_time, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
                eps_estimate = VALUES(eps_estimate),
                eps_actual = VALUES(eps_actual),
                revenue_estimate = VALUES(revenue_estimate),
                revenue_actual = VALUES(revenue_actual),
                report_time = VALUES(report_time),
                updated_at = NOW()
        ");
        
        foreach ($earningsTickers as $tickerData) {
            $stmt->execute([
                $tickerData['ticker'],
                $tickerData['company_name'],
                $tickerData['eps_estimate'],
                $tickerData['eps_actual'],
                $tickerData['revenue_estimate'],
                $tickerData['revenue_actual'],
                $tickerData['report_time']
            ]);
        }
        
        echo "✅ Created entries in TodayEarningsMovements\n";
        
    } else {
        echo "❌ No earnings tickers found\n";
    }
    
    // FINAL SUMMARY
    echo "\n=== FINAL SUMMARY ===\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $totalTickers = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements");
    $stmt->execute();
    $totalMovements = $stmt->fetchColumn();
    
    // Count estimates and actuals
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE eps_estimate IS NOT NULL");
    $stmt->execute();
    $epsEstimates = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE eps_actual IS NOT NULL");
    $stmt->execute();
    $epsActuals = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE revenue_estimate IS NOT NULL");
    $stmt->execute();
    $revenueEstimates = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE revenue_actual IS NOT NULL");
    $stmt->execute();
    $revenueActuals = $stmt->fetchColumn();
    
    echo "📊 Total tickers processed: {$totalProcessed}\n";
    echo "📈 Earnings tickers found: " . count($earningsTickers) . "\n";
    echo "💾 Saved to EarningsTickersToday: {$totalTickers}\n";
    echo "💾 Total in TodayEarningsMovements: {$totalMovements}\n";
    echo "📊 EPS Estimates: {$epsEstimates}\n";
    echo "📊 EPS Actuals: {$epsActuals}\n";
    echo "📊 Revenue Estimates: {$revenueEstimates}\n";
    echo "📊 Revenue Actuals: {$revenueActuals}\n";
    echo "🚀 API Calls: 1 (earnings calendar only)\n";
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "⏱️  Total time: {$executionTime}s\n";
    echo "✅ YAHOO FINANCE TICKERS FETCH COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
