<?php
require_once 'config.php';

echo "=== ALPHA VANTAGE API EXPLORATION ===\n";

$apiKey = 'YFO8D5S1D0E4F80C';
$baseUrl = 'https://www.alphavantage.co/query';

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n";
echo "API Key: {$apiKey}\n\n";

// Test 1: Earnings Calendar for specific ticker
echo "=== TEST 1: EARNINGS CALENDAR (PER TICKER) ===\n";
$testTickers = ['AAPL', 'MSFT', 'ATAT', 'BMO', 'BNS'];

foreach ($testTickers as $ticker) {
    echo "\n--- Testing {$ticker} ---\n";
    
    $url = "{$baseUrl}?function=EARNINGS_CALENDAR&symbol={$ticker}&horizon=3month&apikey={$apiKey}";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if (isset($data['earningsCalendar'])) {
        echo "✅ Found earnings calendar data\n";
        echo "Total entries: " . count($data['earningsCalendar']) . "\n";
        
        // Look for today's earnings
        $todayEarnings = [];
        foreach ($data['earningsCalendar'] as $earning) {
            if ($earning['reportDate'] === $date) {
                $todayEarnings[] = $earning;
            }
        }
        
        if (!empty($todayEarnings)) {
            echo "✅ Found earnings for today:\n";
            foreach ($todayEarnings as $earning) {
                echo "  Date: {$earning['reportDate']}\n";
                echo "  Time: {$earning['time']}\n";
                echo "  EPS Estimate: {$earning['estimate']}\n";
                echo "  Revenue Estimate: {$earning['revenueEstimate']}\n";
                echo "  Currency: {$earning['currency']}\n";
            }
        } else {
            echo "❌ No earnings for today\n";
        }
        
        // Show next few earnings
        echo "\nNext earnings:\n";
        $count = 0;
        foreach ($data['earningsCalendar'] as $earning) {
            if ($count >= 3) break;
            echo "  {$earning['reportDate']} - EPS: {$earning['estimate']}, Revenue: {$earning['revenueEstimate']}\n";
            $count++;
        }
        
    } else {
        echo "❌ No earnings calendar data\n";
        if (isset($data['Note'])) {
            echo "API Note: {$data['Note']}\n";
        }
        if (isset($data['Error Message'])) {
            echo "Error: {$data['Error Message']}\n";
        }
    }
    
    // Rate limiting - Alpha Vantage allows 5 calls per minute
    sleep(12);
}

// Test 2: Earnings (Annual and Quarterly)
echo "\n\n=== TEST 2: EARNINGS (ANNUAL/QUARTERLY) ===\n";
$testTicker = 'AAPL';

echo "\n--- Testing {$testTicker} Annual Earnings ---\n";
$url = "{$baseUrl}?function=EARNINGS&symbol={$testTicker}&apikey={$apiKey}";
$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['annualEarnings'])) {
    echo "✅ Found annual earnings data\n";
    echo "Total annual earnings: " . count($data['annualEarnings']) . "\n";
    
    // Show last 3 annual earnings
    echo "\nLast 3 annual earnings:\n";
    $count = 0;
    foreach ($data['annualEarnings'] as $earning) {
        if ($count >= 3) break;
        echo "  {$earning['fiscalDateEnding']} - EPS: {$earning['reportedEPS']}\n";
        $count++;
    }
}

sleep(12);

echo "\n--- Testing {$testTicker} Quarterly Earnings ---\n";
$url = "{$baseUrl}?function=EARNINGS&symbol={$testTicker}&apikey={$apiKey}";
$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['quarterlyEarnings'])) {
    echo "✅ Found quarterly earnings data\n";
    echo "Total quarterly earnings: " . count($data['quarterlyEarnings']) . "\n";
    
    // Show last 5 quarterly earnings
    echo "\nLast 5 quarterly earnings:\n";
    $count = 0;
    foreach ($data['quarterlyEarnings'] as $earning) {
        if ($count >= 5) break;
        echo "  {$earning['fiscalDateEnding']} - EPS: {$earning['reportedEPS']}, Revenue: {$earning['reportedRevenue']}\n";
        $count++;
    }
}

// Test 3: Company Overview
echo "\n\n=== TEST 3: COMPANY OVERVIEW ===\n";
$testTicker = 'AAPL';

echo "\n--- Testing {$testTicker} Company Overview ---\n";
$url = "{$baseUrl}?function=OVERVIEW&symbol={$testTicker}&apikey={$apiKey}";
$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['Symbol'])) {
    echo "✅ Found company overview data\n";
    echo "Symbol: {$data['Symbol']}\n";
    echo "Name: {$data['Name']}\n";
    echo "Sector: {$data['Sector']}\n";
    echo "Market Cap: {$data['MarketCapitalization']}\n";
    echo "EPS: {$data['EPS']}\n";
    echo "Revenue: {$data['RevenueTTM']}\n";
} else {
    echo "❌ No company overview data\n";
}

echo "\n=== SUMMARY ===\n";
echo "Alpha Vantage API provides:\n";
echo "1. EARNINGS_CALENDAR - Future earnings dates and estimates\n";
echo "2. EARNINGS - Historical earnings data (annual/quarterly)\n";
echo "3. OVERVIEW - Company information and current metrics\n";
echo "\nRate limit: 5 calls per minute (free tier)\n";
echo "Data quality: Good for individual ticker research\n";
echo "Coverage: Limited to specific tickers (not bulk calendar)\n";
?>
