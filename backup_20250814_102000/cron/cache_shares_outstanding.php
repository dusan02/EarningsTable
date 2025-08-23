<?php
/**
 * Optimized Shares Outstanding Cache Script
 * Streamlined version with essential functionality
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../common/Lock.php';
require_once __DIR__ . '/../common/Finnhub.php';

// Lock mechanism
$lock = new Lock('cache_shares_outstanding');
if (!$lock->acquire()) {
    echo "❌ Another process is running\n";
    exit(1);
}
register_shutdown_function(fn() => $lock->release());

$startTime = microtime(true);
$today = date('Y-m-d');

echo "🚀 SHARES CACHE UPDATE\n";

try {
    // Clean old cache entries
    $pdo->exec("DELETE FROM SharesOutstanding WHERE fetched_on < '$today'");
    
    // Get tickers
    $stmt = $pdo->query("SELECT DISTINCT ticker FROM EarningsTickersToday WHERE report_date = CURDATE()");
    $tickers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tickers)) {
        echo "❌ No tickers found\n";
        exit(1);
    }
    
    echo "📊 Processing " . count($tickers) . " tickers\n";
    
    // Prepare statement
    $insertStmt = $pdo->prepare("
        INSERT INTO SharesOutstanding (ticker, shares_outstanding, fetched_on) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        shares_outstanding = VALUES(shares_outstanding), 
        fetched_on = VALUES(fetched_on)
    ");
    
    $successCount = 0;
    
    foreach ($tickers as $ticker) {
        $sharesOutstanding = Finnhub::getSharesOutstanding($ticker);
        
        if ($sharesOutstanding !== null) {
            $insertStmt->execute([$ticker, $sharesOutstanding * 1000000, $today]);
            $successCount++;
        }
    }
    
    $executionTime = round(microtime(true) - $startTime, 2);
    
    echo "✅ SUCCESS: {$successCount} tickers cached\n";
    echo "⏱️  Time: {$executionTime}s\n";
    echo "📊 Performance: " . round($successCount / $executionTime, 1) . " tickers/s\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?> 