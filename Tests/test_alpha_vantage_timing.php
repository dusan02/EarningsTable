<?php
require_once 'config.php';

echo "=== ALPHA VANTAGE TIMING TEST ===\n";

$apiKey = 'YFO8D5S1D0E4F80C';
$baseUrl = 'https://www.alphavantage.co/query';

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

// Test tickers that reported recently (last few days)
$recentTickers = ['AAPL', 'MSFT', 'NVDA', 'TSLA'];

foreach ($recentTickers as $ticker) {
    echo "\n=== TESTING {$ticker} ===\n";
    
    // Test 1: EARNINGS_CALENDAR (future estimates)
    echo "\n1. EARNINGS_CALENDAR (Future Estimates):\n";
    $url = "{$baseUrl}?function=EARNINGS_CALENDAR&symbol={$ticker}&horizon=3month&apikey={$apiKey}";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if (isset($data['earningsCalendar'])) {
        echo "✅ Found earnings calendar data\n";
        echo "Total entries: " . count($data['earningsCalendar']) . "\n";
        
        // Show next few earnings
        echo "\nNext earnings:\n";
        $count = 0;
        foreach ($data['earningsCalendar'] as $earning) {
            if ($count >= 3) break;
            echo "  {$earning['reportDate']} - EPS: " . ($earning['estimate'] ?? 'N/A') . 
                 ", Revenue: " . ($earning['revenueEstimate'] ?? 'N/A') . "\n";
            $count++;
        }
    } else {
        echo "❌ No earnings calendar data\n";
    }
    
    sleep(12); // Rate limiting
    
    // Test 2: EARNINGS (historical data)
    echo "\n2. EARNINGS (Historical Data):\n";
    $url = "{$baseUrl}?function=EARNINGS&symbol={$ticker}&apikey={$apiKey}";
    $response = file_get_contents($url);
    $data = json_decode($response, true);
    
    if (isset($data['quarterlyEarnings'])) {
        echo "✅ Found quarterly earnings data\n";
        echo "Total quarterly earnings: " . count($data['quarterlyEarnings']) . "\n";
        
        // Show most recent earnings
        echo "\nMost recent earnings:\n";
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
        }
        
    } else {
        echo "❌ No quarterly earnings data\n";
    }
    
    sleep(12); // Rate limiting
}

echo "\n\n=== SUMMARY ===\n";
echo "Alpha Vantage behavior:\n";
echo "1. EARNINGS_CALENDAR: Shows future earnings with estimates\n";
echo "2. EARNINGS: Shows historical data AFTER earnings are reported\n";
echo "3. Today's earnings: Will appear in EARNINGS only AFTER they're reported\n";
echo "4. Before report: No entry exists in EARNINGS\n";
echo "5. After report: New entry appears with actual values\n";
echo "\nNote: Revenue data may be missing even after earnings are reported.\n";
?>
