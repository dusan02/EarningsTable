<?php
require_once 'config.php';
require_once 'common/Finnhub.php';

echo "=== CHECKING US TICKERS BMO AND BNS ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

try {
    $finnhub = new Finnhub();
    
    // Check for US BMO specifically
    echo "=== CHECKING US BMO ===\n";
    $bmoResponse = $finnhub->getEarningsCalendar('BMO', $date, $date);
    $bmoData = $bmoResponse['earningsCalendar'] ?? [];
    echo "US BMO results: " . count($bmoData) . "\n";
    if (!empty($bmoData)) {
        foreach ($bmoData as $earning) {
            echo "Ticker: {$earning['symbol']}, Exchange: {$earning['exchange']}, EPS Est: {$earning['epsEstimate']}, Revenue Est: {$earning['revenueEstimate']}\n";
        }
    }
    
    // Check for US BNS specifically
    echo "\n=== CHECKING US BNS ===\n";
    $bnsResponse = $finnhub->getEarningsCalendar('BNS', $date, $date);
    $bnsData = $bnsResponse['earningsCalendar'] ?? [];
    echo "US BNS results: " . count($bnsData) . "\n";
    if (!empty($bnsData)) {
        foreach ($bnsData as $earning) {
            echo "Ticker: {$earning['symbol']}, Exchange: {$earning['exchange']}, EPS Est: {$earning['epsEstimate']}, Revenue Est: {$earning['revenueEstimate']}\n";
        }
    }
    
    // Check all earnings for today to see what exchanges are represented
    echo "\n=== ALL EARNINGS TODAY - EXCHANGE BREAKDOWN ===\n";
    $allResponse = $finnhub->getEarningsCalendar('', $date, $date);
    $allData = $allResponse['earningsCalendar'] ?? [];
    
    $exchanges = [];
    foreach ($allData as $earning) {
        $exchange = $earning['exchange'] ?? 'UNKNOWN';
        if (!isset($exchanges[$exchange])) {
            $exchanges[$exchange] = [];
        }
        $exchanges[$exchange][] = $earning['symbol'];
    }
    
    foreach ($exchanges as $exchange => $symbols) {
        echo "Exchange: {$exchange} - " . count($symbols) . " tickers\n";
        echo "Sample tickers: " . implode(', ', array_slice($symbols, 0, 5)) . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
