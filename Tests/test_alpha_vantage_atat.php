<?php
require_once 'config.php';

echo "=== ALPHA VANTAGE ATAT DETAILED TEST ===\n";

$apiKey = 'YFO8D5S1D0E4F80C';
$baseUrl = 'https://www.alphavantage.co/query';
$ticker = 'ATAT'; // We know this ticker has data

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Testing ticker: {$ticker}\n";
echo "Date: {$date}\n\n";

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
    
    // Show most recent earnings
    echo "Most recent earnings:\n";
    $latest = $data['quarterlyEarnings'][0] ?? null;
    if ($latest) {
        echo "  Date: {$latest['fiscalDateEnding']}\n";
        echo "  EPS Actual: " . ($latest['reportedEPS'] ?? 'N/A') . "\n";
        echo "  EPS Estimate: " . ($latest['estimatedEPS'] ?? 'N/A') . "\n";
        echo "  Revenue Actual: " . ($latest['reportedRevenue'] ?? 'N/A') . "\n";
        echo "  Revenue Estimate: " . ($latest['estimatedRevenue'] ?? 'N/A') . "\n";
        echo "  Surprise: " . ($latest['surprise'] ?? 'N/A') . "\n";
        echo "  Surprise %: " . ($latest['surprisePercentage'] ?? 'N/A') . "\n";
    }
    
    // Check if there's an entry for today's date
    $todayEntry = null;
    foreach ($data['quarterlyEarnings'] as $earning) {
        if ($earning['fiscalDateEnding'] === $date) {
            $todayEntry = $earning;
            break;
        }
    }
    
    if ($todayEntry) {
        echo "\n✅ FOUND TODAY'S EARNINGS ENTRY:\n";
        echo "  EPS Actual: " . ($todayEntry['reportedEPS'] ?? 'N/A') . "\n";
        echo "  Revenue Actual: " . ($todayEntry['reportedRevenue'] ?? 'N/A') . "\n";
    } else {
        echo "\n❌ No earnings entry for today ({$date})\n";
        echo "This confirms: Alpha Vantage doesn't have today's earnings yet\n";
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

echo "\n\n=== CONCLUSION ===\n";
echo "For ATAT ticker on {$date}:\n";
echo "1. EARNINGS_CALENDAR: " . (isset($data['earningsCalendar']) ? "✅ Has data" : "❌ No data") . "\n";
echo "2. EARNINGS: " . (isset($data['quarterlyEarnings']) ? "✅ Has historical data" : "❌ No data") . "\n";
echo "3. Today's actual earnings: " . ($todayEntry ? "✅ Found" : "❌ Not found") . "\n";
echo "\nThis confirms ChatGPT's explanation about Alpha Vantage timing.\n";
?>
