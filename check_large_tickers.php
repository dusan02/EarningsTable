<?php
require_once 'config.php';

echo "=== CHECKING LARGE MARKET CAP TICKERS ===\n";

try {
    // Check for large market cap tickers (>10B)
    $stmt = $pdo->prepare("SELECT ticker, market_cap FROM TodayEarningsMovements WHERE market_cap > 10000000000 ORDER BY market_cap DESC LIMIT 10");
    $stmt->execute();
    
    echo "Large tickers in database (>10B):\n";
    while($row = $stmt->fetch()) {
        echo $row['ticker'] . ': ' . number_format($row['market_cap'] / 1000000000, 1) . 'B' . "\n";
    }
    
    // Check what tickers are in EarningsTickersToday
    echo "\n=== EARNINGS TICKERS TODAY ===\n";
    $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday ORDER BY ticker LIMIT 20");
    $stmt->execute();
    
    echo "First 20 tickers from EarningsTickersToday:\n";
    while($row = $stmt->fetch()) {
        echo $row['ticker'] . " ";
    }
    echo "\n";
    
    // Check specific missing tickers
    $missingTickers = ['BHP', 'PANW', 'GMBXF', 'PPERY', 'TLK', 'MTNOY', 'BPHLY', 'VRNA'];
    echo "\n=== CHECKING MISSING TICKERS ===\n";
    
    foreach ($missingTickers as $ticker) {
        $stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE ticker = ?");
        $stmt->execute([$ticker]);
        $found = $stmt->fetch();
        
        if ($found) {
            echo $ticker . ": FOUND in EarningsTickersToday\n";
        } else {
            echo $ticker . ": NOT FOUND in EarningsTickersToday\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
