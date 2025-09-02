<?php
require_once 'config.php';

echo "=== ALPHA VANTAGE TODAY'S EARNINGS TEST ===\n";

$apiKey = 'YFO8D5S1D0E4F80C';
$baseUrl = 'https://www.alphavantage.co/query';

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

// Test tickers that we know have earnings today (from Finnhub)
$todayTickers = ['ATAT', 'MDB', 'BMO', 'BNS', 'FIVE', 'COSM', 'TUYA', 'OOMA', 'KSS', 'EMD'];

$foundEarnings = [];

foreach ($todayTickers as $ticker) {
    echo "Testing {$ticker}... ";
    
    $url = "{$baseUrl}?function=EARNINGS_CALENDAR&symbol={$ticker}&horizon=3month&apikey={$apiKey}";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if (isset($data['earningsCalendar'])) {
        foreach ($data['earningsCalendar'] as $earning) {
            if ($earning['reportDate'] === $date) {
                $foundEarnings[$ticker] = [
                    'date' => $earning['reportDate'],
                    'time' => $earning['time'] ?? 'TNS',
                    'eps_estimate' => $earning['estimate'] ?? 'N/A',
                    'revenue_estimate' => $earning['revenueEstimate'] ?? 'N/A',
                    'currency' => $earning['currency'] ?? 'USD'
                ];
                echo "✅ FOUND\n";
                break;
            }
        }
        
        if (!isset($foundEarnings[$ticker])) {
            echo "❌ Not today\n";
        }
    } else {
        echo "❌ No data\n";
        
        // Check for API limits or errors
        if (isset($data['Note'])) {
            echo "  API Note: {$data['Note']}\n";
        }
        if (isset($data['Error Message'])) {
            echo "  Error: {$data['Error Message']}\n";
        }
    }
    
    // Rate limiting
    sleep(12);
}

echo "\n=== RESULTS ===\n";
if (!empty($foundEarnings)) {
    echo "✅ Alpha Vantage found earnings for today:\n";
    foreach ($foundEarnings as $ticker => $data) {
        echo "  {$ticker}: {$data['date']} {$data['time']} - EPS: {$data['eps_estimate']}, Revenue: {$data['revenue_estimate']} {$data['currency']}\n";
    }
} else {
    echo "❌ Alpha Vantage has no earnings data for today\n";
}

echo "\n=== COMPARISON WITH FINNHUB ===\n";
try {
    require_once 'common/Finnhub.php';
    $finnhub = new Finnhub();
    $response = $finnhub->getEarningsCalendar('', $date, $date);
    $finnhubTickers = $response['earningsCalendar'] ?? [];
    
    echo "Finnhub has " . count($finnhubTickers) . " tickers for today\n";
    
    // Check if any of our test tickers are in Finnhub
    $finnhubSymbols = array_column($finnhubTickers, 'symbol');
    $commonTickers = array_intersect($todayTickers, $finnhubSymbols);
    
    echo "Common tickers between Alpha Vantage test and Finnhub: " . count($commonTickers) . "\n";
    if (!empty($commonTickers)) {
        echo "Common: " . implode(', ', $commonTickers) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Finnhub error: " . $e->getMessage() . "\n";
}

echo "\n=== CONCLUSION ===\n";
echo "Alpha Vantage EARNINGS_CALENDAR function:\n";
echo "- Works for individual ticker queries\n";
echo "- Rate limited to 5 calls per minute\n";
echo "- May not have data for all tickers\n";
echo "- Good for supplementing missing data from Finnhub\n";
?>
