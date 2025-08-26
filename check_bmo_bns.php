<?php
require_once 'config.php';
require_once 'common/Finnhub.php';

echo "=== CHECKING FOR BMO AND BNS ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

try {
    $finnhub = new Finnhub();
    
    // Check for BMO specifically
    echo "=== CHECKING BMO ===\n";
    $bmoResponse = $finnhub->getEarningsCalendar('BMO', $date, $date);
    $bmoData = $bmoResponse['earningsCalendar'] ?? [];
    echo "BMO results: " . count($bmoData) . "\n";
    if (!empty($bmoData)) {
        print_r($bmoData);
    }
    
    // Check for BNS specifically
    echo "\n=== CHECKING BNS ===\n";
    $bnsResponse = $finnhub->getEarningsCalendar('BNS', $date, $date);
    $bnsData = $bnsResponse['earningsCalendar'] ?? [];
    echo "BNS results: " . count($bnsData) . "\n";
    if (!empty($bnsData)) {
        print_r($bnsData);
    }
    
    // Check broader date range for these tickers
    echo "\n=== CHECKING BROADER DATE RANGE ===\n";
    $yesterday = (clone $usDate)->modify('-1 day')->format('Y-m-d');
    $tomorrow = (clone $usDate)->modify('+1 day')->format('Y-m-d');
    
    echo "Checking range: {$yesterday} to {$tomorrow}\n";
    
    $bmoResponse = $finnhub->getEarningsCalendar('BMO', $yesterday, $tomorrow);
    $bmoData = $bmoResponse['earningsCalendar'] ?? [];
    echo "BMO in broader range: " . count($bmoData) . "\n";
    if (!empty($bmoData)) {
        print_r($bmoData);
    }
    
    $bnsResponse = $finnhub->getEarningsCalendar('BNS', $yesterday, $tomorrow);
    $bnsData = $bnsResponse['earningsCalendar'] ?? [];
    echo "BNS in broader range: " . count($bnsData) . "\n";
    if (!empty($bnsData)) {
        print_r($bnsData);
    }
    
    // Check if they might be listed under different symbols
    echo "\n=== CHECKING ALTERNATIVE SYMBOLS ===\n";
    $alternativeSymbols = ['BMO', 'BNS', 'BMO.TO', 'BNS.TO', 'BMO:US', 'BNS:US'];
    
    foreach ($alternativeSymbols as $symbol) {
        $response = $finnhub->getEarningsCalendar($symbol, $date, $date);
        $data = $response['earningsCalendar'] ?? [];
        if (!empty($data)) {
            echo "Found {$symbol}: " . count($data) . " results\n";
            print_r($data);
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
