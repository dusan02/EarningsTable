<?php
require_once 'config.php';

echo "=== ADDING MISSING TICKERS TO EARNINGS TICKERS TODAY ===\n";

try {
    // Get today's date
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    // Missing tickers that we fetched via Yahoo Finance
    $missingTickers = ['BHP', 'GMBXF', 'PPERY', 'TLK', 'MTNOY', 'BPHLY', 'VRNA'];
    
    $pdo->beginTransaction();
    
    foreach ($missingTickers as $ticker) {
        // Check if already exists
        $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE ticker = ? AND report_date = ?");
        $stmt->execute([$ticker, $date]);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            // Add to EarningsTickersToday
            $stmt = $pdo->prepare("
                INSERT INTO EarningsTickersToday (
                    ticker, report_date, report_time, eps_estimate, revenue_estimate
                ) VALUES (?, ?, 'TNS', NULL, NULL)
            ");
            $stmt->execute([$ticker, $date]);
            
            echo "✅ Added {$ticker} to EarningsTickersToday\n";
        } else {
            echo "ℹ️  {$ticker} already exists in EarningsTickersToday\n";
        }
    }
    
    $pdo->commit();
    echo "\n✅ SUCCESS: Missing tickers added to EarningsTickersToday\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== COMPLETE ===\n";
?>
