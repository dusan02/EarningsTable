<?php
/**
 * Update Polygon Data Every 5 Minutes
 * Updates market cap, prices, company names, and price changes
 * Runs every 5 minutes after initial setup
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';

// Lock mechanism
$lock = new Lock('polygon_5min_update');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
echo "🚀 POLYGON 5-MINUTE UPDATE STARTED\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "📅 Date: {$date}\n";
    echo "⏰ Time: " . $usDate->format('H:i:s') . " NY\n\n";
    
    // STEP 1: Get all earnings tickers that need updates
    echo "=== STEP 1: GETTING TICKERS FOR UPDATE ===\n";
    $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
    $stmt->execute([$date]);
    $earningsTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($earningsTickers) . " earnings tickers to update\n";
    
    if (empty($earningsTickers)) {
        echo "❌ No earnings tickers found\n";
        exit(1);
    }
    
    // STEP 2: Update prices and price changes from Polygon (batch)
    echo "\n=== STEP 2: UPDATING PRICES FROM POLYGON ===\n";
    
    $polygonScript = __DIR__ . '/fetch_polygon_batch_earnings.php';
    if (file_exists($polygonScript)) {
        echo "Running Polygon price update...\n";
        $output = shell_exec("D:\\xampp\\php\\php.exe \"{$polygonScript}\" 2>&1");
        echo $output;
    } else {
        echo "❌ Polygon batch script not found\n";
    }
    
    // STEP 3: Update market cap and company names from Polygon (concurrent)
    echo "\n=== STEP 3: UPDATING MARKET CAP FROM POLYGON ===\n";
    
    $marketCapScript = __DIR__ . '/fetch_market_cap_polygon_batch.php';
    if (file_exists($marketCapScript)) {
        echo "Running Polygon market cap update...\n";
        $output = shell_exec("D:\\xampp\\php\\php.exe \"{$marketCapScript}\" 2>&1");
        echo $output;
    } else {
        echo "❌ Polygon market cap script not found\n";
    }
    
    // STEP 4: Summary
    echo "\n=== STEP 4: UPDATE SUMMARY ===\n";
    
    // Count updated records
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE current_price > 0");
    $stmt->execute();
    $withPrices = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE market_cap > 0");
    $stmt->execute();
    $withMarketCap = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE price_change_percent IS NOT NULL");
    $stmt->execute();
    $withPriceChanges = $stmt->fetchColumn();
    
    echo "📊 Records with prices: {$withPrices}\n";
    echo "📊 Records with market cap: {$withMarketCap}\n";
    echo "📊 Records with price changes: {$withPriceChanges}\n";
    
    // Show recent price changes
    echo "\n=== RECENT PRICE CHANGES ===\n";
    $stmt = $pdo->prepare("
        SELECT ticker, current_price, price_change_percent, updated_at
        FROM TodayEarningsMovements 
        WHERE price_change_percent IS NOT NULL
        ORDER BY updated_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentChanges = $stmt->fetchAll();
    
    foreach ($recentChanges as $change) {
        $priceChange = $change['price_change_percent'] ?? 0;
        $updatedTime = date('H:i:s', strtotime($change['updated_at']));
        echo sprintf("%-6s | $%-8.2f | %+.2f%% | %s\n",
            $change['ticker'],
            $change['current_price'],
            $priceChange,
            $updatedTime
        );
    }
    
    $executionTime = round(microtime(true) - $startTime, 2);
    echo "\n⏱️  Update time: {$executionTime}s\n";
    echo "✅ POLYGON 5-MINUTE UPDATE COMPLETED\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
