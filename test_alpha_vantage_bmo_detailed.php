<?php
require_once 'config.php';

echo "=== ALPHA VANTAGE BMO DETAILED TEST ===\n";

$apiKey = 'YFO8D5S1D0E4F80C';
$baseUrl = 'https://www.alphavantage.co/query';
$ticker = 'BMO';

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Testing ticker: {$ticker}\n";
echo "Date: {$date}\n";
echo "API Key: {$apiKey}\n\n";

// Test 1: EARNINGS_CALENDAR (future estimates)
echo "=== TEST 1: EARNINGS_CALENDAR (Future Estimates) ===\n";
$url = "{$baseUrl}?function=EARNINGS_CALENDAR&symbol={$ticker}&horizon=3month&apikey={$apiKey}";
$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['earningsCalendar'])) {
    echo "✅ Found earnings calendar data\n";
    echo "Total entries: " . count($data['earningsCalendar']) . "\n\n";
    
    // Look for today's earnings
    $todayEarnings = [];
    foreach ($data['earningsCalendar'] as $earning) {
        if ($earning['reportDate'] === $date) {
            $todayEarnings[] = $earning;
        }
    }
    
    if (!empty($todayEarnings)) {
        echo "✅ FOUND EARNINGS FOR TODAY:\n";
        foreach ($todayEarnings as $earning) {
            echo "  Date: {$earning['reportDate']}\n";
            echo "  Time: " . ($earning['time'] ?? 'N/A') . "\n";
            echo "  EPS Estimate: " . ($earning['estimate'] ?? 'N/A') . "\n";
            echo "  Revenue Estimate: " . ($earning['revenueEstimate'] ?? 'N/A') . "\n";
            echo "  Currency: " . ($earning['currency'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ No earnings for today\n";
    }
    
    // Show all entries
    echo "\nAll earnings calendar entries:\n";
    foreach ($data['earningsCalendar'] as $earning) {
        echo "  {$earning['reportDate']} - EPS: " . ($earning['estimate'] ?? 'N/A') . 
             ", Revenue: " . ($earning['revenueEstimate'] ?? 'N/A') . "\n";
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

sleep(12); // Rate limiting

// Test 2: EARNINGS (historical data)
echo "\n\n=== TEST 2: EARNINGS (Historical Data) ===\n";
$url = "{$baseUrl}?function=EARNINGS&symbol={$ticker}&apikey={$apiKey}";
$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['quarterlyEarnings'])) {
    echo "✅ Found quarterly earnings data\n";
    echo "Total quarterly earnings: " . count($data['quarterlyEarnings']) . "\n\n";
    
    // Look for recent earnings
    echo "Recent quarterly earnings:\n";
    $count = 0;
    foreach ($data['quarterlyEarnings'] as $earning) {
        if ($count >= 5) break;
        echo "  {$earning['fiscalDateEnding']}:\n";
        echo "    EPS Actual: " . ($earning['reportedEPS'] ?? 'N/A') . "\n";
        echo "    Revenue Actual: " . ($earning['reportedRevenue'] ?? 'N/A') . "\n";
        echo "    Estimated EPS: " . ($earning['estimatedEPS'] ?? 'N/A') . "\n";
        echo "    Estimated Revenue: " . ($earning['estimatedRevenue'] ?? 'N/A') . "\n";
        echo "    Surprise: " . ($earning['surprise'] ?? 'N/A') . "\n";
        echo "    Surprise Percentage: " . ($earning['surprisePercentage'] ?? 'N/A') . "\n\n";
        $count++;
    }
} else {
    echo "❌ No quarterly earnings data\n";
    if (isset($data['Note'])) {
        echo "API Note: {$data['Note']}\n";
    }
    if (isset($data['Error Message'])) {
        echo "Error: {$data['Error Message']}\n";
    }
}

sleep(12); // Rate limiting

// Test 3: OVERVIEW (current company data)
echo "\n\n=== TEST 3: OVERVIEW (Current Company Data) ===\n";
$url = "{$baseUrl}?function=OVERVIEW&symbol={$ticker}&apikey={$apiKey}";
$response = file_get_contents($url);
$data = json_decode($response, true);

if (isset($data['Symbol'])) {
    echo "✅ Found company overview data\n";
    echo "Symbol: {$data['Symbol']}\n";
    echo "Name: {$data['Name']}\n";
    echo "Sector: {$data['Sector']}\n";
    echo "Industry: {$data['Industry']}\n";
    echo "Market Cap: {$data['MarketCapitalization']}\n";
    echo "EPS: {$data['EPS']}\n";
    echo "Revenue TTM: {$data['RevenueTTM']}\n";
    echo "Profit Margin: {$data['ProfitMargin']}\n";
    echo "Operating Margin TTM: {$data['OperatingMarginTTM']}\n";
} else {
    echo "❌ No company overview data\n";
    if (isset($data['Note'])) {
        echo "API Note: {$data['Note']}\n";
    }
    if (isset($data['Error Message'])) {
        echo "Error: {$data['Error Message']}\n";
    }
}

echo "\n\n=== SUMMARY FOR BMO ===\n";
echo "Alpha Vantage provides for BMO:\n";
echo "1. EARNINGS_CALENDAR: Future earnings dates and estimates\n";
echo "2. EARNINGS: Historical EPS/Revenue (actual and estimated)\n";
echo "3. OVERVIEW: Current company metrics\n";
echo "\nNote: Alpha Vantage does NOT provide real-time earnings data for today.\n";
echo "It provides historical data and future estimates only.\n";
?>
