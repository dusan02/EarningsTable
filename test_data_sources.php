<?php
require_once 'config.php';
require_once 'common/YahooFinance.php';

echo "=== TESTING DATA SOURCES ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

// Test 1: Yahoo Finance
echo "=== TEST 1: YAHOO FINANCE ===\n";
try {
    $yahoo = new YahooFinance();
    $result = $yahoo->getEarningsCalendar($date);
    
    if (isset($result['error'])) {
        echo "❌ Yahoo Finance Error: " . $result['error'] . "\n";
    } else {
        echo "✅ Yahoo Finance Success: " . $result['count'] . " tickers\n";
        
        if (!empty($result['earnings'])) {
            echo "\nFirst 10 Yahoo Finance tickers:\n";
            for ($i = 0; $i < min(10, count($result['earnings'])); $i++) {
                $earning = $result['earnings'][$i];
                echo "  {$earning['symbol']} - {$earning['company_name']} - EPS: {$earning['eps_estimate']}\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Yahoo Finance Exception: " . $e->getMessage() . "\n";
}

echo "\n=== TEST 2: ALPHA VANTAGE ===\n";

// Test Alpha Vantage for specific tickers
$testTickers = ['BMO', 'BNS', 'AAPL', 'MSFT', 'ATAT', 'MDB'];
$alphaVantageResults = [];

foreach ($testTickers as $ticker) {
    try {
        $url = "https://www.alphavantage.co/query?function=EARNINGS_CALENDAR&symbol={$ticker}&horizon=3month&apikey=YFO8D5S1D0E4F80C";
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if (isset($data['earningsCalendar'])) {
            foreach ($data['earningsCalendar'] as $earning) {
                if ($earning['reportDate'] === $date) {
                    $alphaVantageResults[$ticker] = [
                        'eps_estimate' => $earning['estimate'] ?? 'N/A',
                        'revenue_estimate' => $earning['revenueEstimate'] ?? 'N/A',
                        'report_time' => $earning['time'] ?? 'TNS'
                    ];
                    break;
                }
            }
        }
        
        // Rate limiting for Alpha Vantage (5 calls per minute)
        sleep(12);
        
    } catch (Exception $e) {
        echo "❌ Alpha Vantage Error for {$ticker}: " . $e->getMessage() . "\n";
    }
}

if (!empty($alphaVantageResults)) {
    echo "✅ Alpha Vantage found data for today:\n";
    foreach ($alphaVantageResults as $ticker => $data) {
        echo "  {$ticker}: EPS: {$data['eps_estimate']}, Revenue: {$data['revenue_estimate']}, Time: {$data['report_time']}\n";
    }
} else {
    echo "❌ Alpha Vantage: No data found for today\n";
}

echo "\n=== TEST 3: COMPARE WITH FINNHUB ===\n";

// Get Finnhub data for comparison
try {
    require_once 'common/Finnhub.php';
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $finnhubTickers = $response['earningsCalendar'] ?? [];
    
    echo "✅ Finnhub: " . count($finnhubTickers) . " tickers\n";
    
    // Show first 10 Finnhub tickers
    if (!empty($finnhubTickers)) {
        echo "\nFirst 10 Finnhub tickers:\n";
        for ($i = 0; $i < min(10, count($finnhubTickers)); $i++) {
            $earning = $finnhubTickers[$i];
            echo "  {$earning['symbol']} - EPS: {$earning['epsEstimate']}, Revenue: {$earning['revenueEstimate']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Finnhub Error: " . $e->getMessage() . "\n";
}

echo "\n=== SUMMARY ===\n";
echo "This test shows which data sources have earnings data for today.\n";
echo "If Yahoo Finance shows 0 tickers, it might be a scraping issue.\n";
echo "Alpha Vantage has rate limits (5 calls per minute), so we test only specific tickers.\n";
?>
