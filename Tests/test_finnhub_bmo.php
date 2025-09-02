<?php
require_once 'config.php';

echo "=== FINNHUB BMO TEST ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

try {
    require_once 'common/Finnhub.php';
    $finnhub = new Finnhub();
    
    // Test 1: Get all earnings for today
    echo "=== TEST 1: ALL EARNINGS TODAY ===\n";
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $earnings = $response['earningsCalendar'] ?? [];
    
    echo "Total earnings today: " . count($earnings) . "\n\n";
    
    // Test 2: Look specifically for BMO
    echo "=== TEST 2: LOOKING FOR BMO ===\n";
    $bmoData = null;
    foreach ($earnings as $earning) {
        if ($earning['symbol'] === 'BMO') {
            $bmoData = $earning;
            break;
        }
    }
    
    if ($bmoData) {
        echo "✅ BMO FOUND in Finnhub today!\n";
        echo "Symbol: {$bmoData['symbol']}\n";
        echo "Date: {$bmoData['date']}\n";
        echo "Time: " . ($bmoData['time'] ?? 'N/A') . "\n";
        echo "EPS Estimate: " . ($bmoData['epsEstimate'] ?? 'N/A') . "\n";
        echo "Revenue Estimate: " . ($bmoData['revenueEstimate'] ?? 'N/A') . "\n";
        echo "Currency: " . ($bmoData['currency'] ?? 'N/A') . "\n";
    } else {
        echo "❌ BMO NOT found in Finnhub today\n";
        
        // Show some tickers that ARE in Finnhub
        echo "\nSample tickers from Finnhub today:\n";
        $count = 0;
        foreach ($earnings as $earning) {
            if ($count >= 10) break;
            echo "  {$earning['symbol']} - EPS: " . ($earning['epsEstimate'] ?? 'N/A') . 
                 ", Revenue: " . ($earning['revenueEstimate'] ?? 'N/A') . "\n";
            $count++;
        }
    }
    
} catch (Exception $e) {
    echo "❌ Finnhub error: " . $e->getMessage() . "\n";
}

echo "\n=== CONCLUSION ===\n";
echo "Alpha Vantage: ❌ No data for BMO\n";
echo "Finnhub: " . ($bmoData ? "✅ Has BMO data" : "❌ No BMO data") . "\n";
?>
