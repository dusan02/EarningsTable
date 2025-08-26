<?php
require_once 'config.php';
require_once 'common/Finnhub.php';

echo "=== FINNHUB TICKERS FOR TODAY ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

try {
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $earningsData = $response['earningsCalendar'] ?? [];
    
    if (empty($earningsData)) {
        echo "❌ No earnings data found for today\n";
        exit(1);
    }
    
    echo "✅ Found " . count($earningsData) . " earnings reports today\n\n";
    
    // Display all tickers with details
    echo "=== ALL TICKERS FROM FINNHUB ===\n";
    $tickers = [];
    
    foreach ($earningsData as $earning) {
        $ticker = $earning['symbol'] ?? '';
        $epsEstimate = $earning['epsEstimate'] ?? null;
        $epsActual = $earning['epsActual'] ?? null;
        $revenueEstimate = $earning['revenueEstimate'] ?? null;
        $revenueActual = $earning['revenueActual'] ?? null;
        $quarter = $earning['quarter'] ?? null;
        $year = $earning['year'] ?? null;
        
        $tickers[] = $ticker;
        
        echo "Ticker: {$ticker}\n";
        echo "  EPS Estimate: " . ($epsEstimate ?? 'N/A') . "\n";
        echo "  EPS Actual: " . ($epsActual ?? 'N/A') . "\n";
        echo "  Revenue Estimate: " . ($revenueEstimate ?? 'N/A') . "\n";
        echo "  Revenue Actual: " . ($revenueActual ?? 'N/A') . "\n";
        echo "  Quarter: " . ($quarter ?? 'N/A') . "\n";
        echo "  Year: " . ($year ?? 'N/A') . "\n";
        echo "  ---\n";
    }
    
    echo "\n=== TICKER SUMMARY ===\n";
    echo "Total tickers: " . count($tickers) . "\n";
    echo "Ticker list: " . implode(', ', $tickers) . "\n";
    
    // Check which tickers have actual values
    $withEpsActual = 0;
    $withRevenueActual = 0;
    
    foreach ($earningsData as $earning) {
        if (!empty($earning['epsActual'])) $withEpsActual++;
        if (!empty($earning['revenueActual'])) $withRevenueActual++;
    }
    
    echo "\n=== ACTUAL VALUES SUMMARY ===\n";
    echo "Tickers with EPS actual: {$withEpsActual}\n";
    echo "Tickers with Revenue actual: {$withRevenueActual}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
