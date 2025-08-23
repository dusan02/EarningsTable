<?php
require_once 'config.php';
require_once 'utils/database.php';

echo "=== DIRECT API TEST ===\n\n";

try {
    // Use US Eastern Time to match the cron jobs
    $timezone = new DateTimeZone('America/New_York');
    $usDate = new DateTime('now', $timezone);
    $date = $usDate->format('Y-m-d');
    
    echo "Date: " . $date . "\n\n";
    
    $earnings = getEarningsDataWithMarketCap($pdo, $date);
    
    echo "✅ API Function: " . count($earnings) . " records\n\n";
    
    // Check first 10 records for Market Cap DESC sorting
    echo "🏆 FIRST 10 RECORDS (should be sorted by Market Cap DESC):\n";
    for ($i = 0; $i < min(10, count($earnings)); $i++) {
        $item = $earnings[$i];
        $marketCap = isset($item['market_cap']) ? $item['market_cap'] : 0;
        $marketCapFormatted = $marketCap > 0 ? number_format($marketCap / 1000000000, 2) . 'B' : 'N/A';
        
        echo sprintf("   %2d. %-6s | Market Cap: %-10s | Price: $%-8.2f\n", 
            $i + 1, 
            $item['ticker'], 
            $marketCapFormatted,
            $item['current_price'] ?? 0
        );
    }
    
    // Verify sorting
    $isSorted = true;
    for ($i = 1; $i < min(10, count($earnings)); $i++) {
        $prevMC = $earnings[$i-1]['market_cap'] ?? 0;
        $currMC = $earnings[$i]['market_cap'] ?? 0;
        
        if ($prevMC < $currMC && $prevMC > 0 && $currMC > 0) {
            $isSorted = false;
            break;
        }
    }
    
    echo "\n" . ($isSorted ? "✅" : "❌") . " SORTING VERIFICATION: " . 
         ($isSorted ? "Data is correctly sorted by Market Cap DESC" : "Data is NOT sorted correctly") . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
