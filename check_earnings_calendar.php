<?php
require_once 'config.php';
require_once 'common/Finnhub.php';

echo "=== CHECKING EARNINGS CALENDAR FOR BMO AND BNS ===\n";

// Get date range (yesterday to tomorrow)
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$yesterday = (clone $usDate)->modify('-1 day')->format('Y-m-d');
$today = $usDate->format('Y-m-d');
$tomorrow = (clone $usDate)->modify('+1 day')->format('Y-m-d');

echo "Checking range: {$yesterday} to {$tomorrow}\n\n";

try {
    $finnhub = new Finnhub();
    
    // Check broader date range for BMO
    echo "=== CHECKING BMO IN BROADER RANGE ===\n";
    $bmoResponse = $finnhub->getEarningsCalendar('BMO', $yesterday, $tomorrow);
    $bmoData = $bmoResponse['earningsCalendar'] ?? [];
    echo "BMO results: " . count($bmoData) . "\n";
    if (!empty($bmoData)) {
        foreach ($bmoData as $earning) {
            echo "Date: {$earning['date']}, Symbol: {$earning['symbol']}, EPS Est: {$earning['epsEstimate']}, Revenue Est: {$earning['revenueEstimate']}\n";
        }
    }
    
    // Check broader date range for BNS
    echo "\n=== CHECKING BNS IN BROADER RANGE ===\n";
    $bnsResponse = $finnhub->getEarningsCalendar('BNS', $yesterday, $tomorrow);
    $bnsData = $bnsResponse['earningsCalendar'] ?? [];
    echo "BNS results: " . count($bnsData) . "\n";
    if (!empty($bnsData)) {
        foreach ($bnsData as $earning) {
            echo "Date: {$earning['date']}, Symbol: {$earning['symbol']}, EPS Est: {$earning['epsEstimate']}, Revenue Est: {$earning['revenueEstimate']}\n";
        }
    }
    
    // Check if there are any earnings reports for today at all
    echo "\n=== ALL EARNINGS TODAY ===\n";
    $allResponse = $finnhub->getEarningsCalendar('', $today, $today);
    $allData = $allResponse['earningsCalendar'] ?? [];
    echo "Total earnings today: " . count($allData) . "\n";
    
    // Show first 10 tickers
    if (!empty($allData)) {
        echo "First 10 tickers:\n";
        for ($i = 0; $i < min(10, count($allData)); $i++) {
            $earning = $allData[$i];
            echo "  {$earning['symbol']} - EPS Est: {$earning['epsEstimate']}, Revenue Est: {$earning['revenueEstimate']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
