<?php
require_once 'config.php';
require_once 'common/Finnhub.php';

echo "=== EARNINGS TICKERS DISCOVERY ONLY ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

// STEP 1: Get tickers from Finnhub (primary source)
echo "=== STEP 1: FINNHUB (PRIMARY SOURCE) ===\n";
$finnhubTickers = [];
$finnhubData = [];
try {
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $finnhubTickers = $response['earningsCalendar'] ?? [];
    
    // Store Finnhub data with EPS/Revenue estimates
    foreach ($finnhubTickers as $earning) {
        $symbol = $earning['symbol'] ?? '';
        if (!empty($symbol)) {
            $finnhubData[$symbol] = [
                'eps_estimate' => $earning['epsEstimate'] ?? null,
                'revenue_estimate' => $earning['revenueEstimate'] ?? null,
                'report_time' => $earning['time'] ?? 'TNS',
                'source' => 'Finnhub'
            ];
        }
    }
    
    echo "✅ Finnhub: " . count($finnhubTickers) . " tickers with EPS/Revenue data\n";
} catch (Exception $e) {
    echo "❌ Finnhub error: " . $e->getMessage() . "\n";
}

// STEP 2: Yahoo Finance removed - using only Finnhub as primary source
echo "\n=== STEP 2: YAHOO FINANCE REMOVED ===\n";
echo "✅ Using only Finnhub as primary source for better stability\n";

// STEP 3: Using only Finnhub data (no missing tickers logic needed)
echo "\n=== STEP 3: USING FINNHUB DATA ONLY ===\n";
$allTickers = $finnhubData;
echo "Total unique tickers: " . count($allTickers) . "\n";

// Display all tickers
echo "\n=== ALL EARNINGS TICKERS FOR TODAY ===\n";
foreach ($allTickers as $ticker => $data) {
    $eps = $data['eps_estimate'] ?? 'N/A';
    $revenue = $data['revenue_estimate'] ?? 'N/A';
    $time = $data['report_time'];
    echo "  {$ticker}: EPS={$eps}, Revenue={$revenue}, Time={$time}\n";
}

echo "\n=== SUMMARY ===\n";
echo "✅ Earnings tickers discovery completed!\n";
echo "📊 Found " . count($allTickers) . " tickers reporting earnings today\n";
echo "📊 These tickers are ready for market data fetching\n";
echo "📊 No Polygon API calls made - only Finnhub earnings calendar\n";
?>
