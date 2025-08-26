<?php
require_once 'config.php';
require_once 'common/YahooFinance.php';

echo "=== TESTING YAHOO FINANCE SCRAPER ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

try {
    $yahoo = new YahooFinance();
    
    // Test 1: Get earnings calendar
    echo "=== TEST 1: GETTING EARNINGS CALENDAR ===\n";
    $result = $yahoo->getEarningsCalendar($date);
    
    if (isset($result['error'])) {
        echo "❌ Error: " . $result['error'] . "\n";
    } else {
        echo "✅ Success: Found " . $result['count'] . " earnings\n";
        
        // Show first 10 tickers
        if (!empty($result['earnings'])) {
            echo "\nFirst 10 tickers:\n";
            for ($i = 0; $i < min(10, count($result['earnings'])); $i++) {
                $earning = $result['earnings'][$i];
                echo "  {$earning['symbol']} - {$earning['company_name']} - EPS: {$earning['eps_estimate']}\n";
            }
        }
    }
    
    // Test 2: Check specific tickers
    echo "\n=== TEST 2: CHECKING SPECIFIC TICKERS ===\n";
    $testTickers = ['BMO', 'BNS', 'AAPL', 'MSFT'];
    
    foreach ($testTickers as $ticker) {
        $hasEarnings = $yahoo->hasEarningsOnDate($ticker, $date);
        echo "{$ticker}: " . ($hasEarnings ? "✅ Has earnings" : "❌ No earnings") . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETED ===\n";
?>
