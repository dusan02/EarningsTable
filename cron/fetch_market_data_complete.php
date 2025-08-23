<?php
/**
 * Complete Market Data Fetch
 * Orchestrates all market data fetching at 03:00h NY time
 * - Polygon batch prices and price changes
 * - Polygon ticker details for market cap and company names
 * - Size classification and market cap diff calculations
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';

// Lock mechanism
$lock = new Lock('market_data_complete');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 COMPLETE MARKET DATA FETCH STARTED (03:00h)\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n\n";
    
    // STEP 1: Get all earnings tickers
    echo "=== STEP 1: GETTING ALL EARNINGS TICKERS ===\n";
    $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $earningsTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($earningsTickers) . " earnings tickers to process\n";
    
    if (empty($earningsTickers)) {
        echo "❌ No earnings tickers found\n";
        exit(1);
    }
    
    // STEP 2: Fetch prices and price changes from Polygon (batch)
    echo "\n=== STEP 2: FETCHING PRICES FROM POLYGON (BATCH) ===\n";
    
    $polygonScript = __DIR__ . '/fetch_polygon_batch_earnings.php';
    if (file_exists($polygonScript)) {
        echo "Running Polygon batch script...\n";
        $output = shell_exec("D:\\xampp\\php\\php.exe \"{$polygonScript}\" 2>&1");
        echo $output;
    } else {
        echo "❌ Polygon batch script not found\n";
    }
    
    // STEP 3: Fetch market cap and company names from Polygon (concurrent)
    echo "\n=== STEP 3: FETCHING MARKET CAP FROM POLYGON (CONCURRENT) ===\n";
    
    $marketCapScript = __DIR__ . '/fetch_market_cap_polygon_batch.php';
    if (file_exists($marketCapScript)) {
        echo "Running Polygon market cap script...\n";
        $output = shell_exec("D:\\xampp\\php\\php.exe \"{$marketCapScript}\" 2>&1");
        echo $output;
    } else {
        echo "❌ Polygon market cap script not found\n";
    }
    
    // STEP 4: Final summary and verification
    echo "\n=== STEP 4: FINAL VERIFICATION ===\n";
    
    // Count records with different data types
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE current_price > 0");
    $stmt->execute();
    $withPrices = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE market_cap > 0");
    $stmt->execute();
    $withMarketCap = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE company_name IS NOT NULL AND company_name != ticker");
    $stmt->execute();
    $withCompanyNames = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE price_change_percent IS NOT NULL");
    $stmt->execute();
    $withPriceChanges = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE size IS NOT NULL AND size != 'Unknown'");
    $stmt->execute();
    $withSize = $stmt->fetchColumn();
    
    echo "📊 Records with prices: {$withPrices}\n";
    echo "📊 Records with market cap: {$withMarketCap}\n";
    echo "📊 Records with company names: {$withCompanyNames}\n";
    echo "📊 Records with price changes: {$withPriceChanges}\n";
    echo "📊 Records with size classification: {$withSize}\n";
    
    // Show top 5 tickers by market cap
    echo "\n=== TOP 5 TICKERS BY MARKET CAP ===\n";
    $stmt = $pdo->prepare("
        SELECT ticker, company_name, current_price, market_cap, size, price_change_percent
        FROM TodayEarningsMovements 
        WHERE market_cap > 0
        ORDER BY market_cap DESC
        LIMIT 5
    ");
    $stmt->execute();
    $topTickers = $stmt->fetchAll();
    
    foreach ($topTickers as $ticker) {
        $marketCapB = number_format($ticker['market_cap'] / 1000000000, 1);
        $priceChange = $ticker['price_change_percent'] ?? 0;
        echo sprintf("%-6s | %-30s | $%-8.2f | $%-6sB (%s) | %+.2f%%\n",
            $ticker['ticker'],
            substr($ticker['company_name'] ?? $ticker['ticker'], 0, 30),
            $ticker['current_price'],
            $marketCapB,
            $ticker['size'],
            $priceChange
        );
    }
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "\n⏱️  Total execution time: {$executionTime}s\n";
    echo "✅ COMPLETE MARKET DATA FETCH COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
