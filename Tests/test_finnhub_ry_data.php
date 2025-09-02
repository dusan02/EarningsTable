<?php
require_once 'config.php';
require_once 'common/Finnhub.php';

echo "=== FINNHUB RY DATA TEST ===\n";

$ticker = 'RY';
echo "Testing ticker: {$ticker}\n\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: {$date}\n\n";

try {
    // Test 1: Earnings Calendar (today's earnings)
    echo "=== TEST 1: EARNINGS CALENDAR (Today) ===\n";
    $response = Finnhub::getEarningsCalendar('', $date, $date);
    
    if (isset($response['earningsCalendar'])) {
        echo "✅ Found earnings calendar data\n";
        echo "Total entries: " . count($response['earningsCalendar']) . "\n\n";
        
        // Look for RY specifically
        $ryData = null;
        foreach ($response['earningsCalendar'] as $earning) {
            if ($earning['symbol'] === $ticker) {
                $ryData = $earning;
                break;
            }
        }
        
        if ($ryData) {
            echo "✅ FOUND RY DATA FOR TODAY:\n";
            echo "  Symbol: {$ryData['symbol']}\n";
            echo "  Date: {$ryData['date']}\n";
            echo "  Quarter: {$ryData['quarter']}\n";
            echo "  Year: {$ryData['year']}\n";
            echo "  EPS Estimate: " . ($ryData['epsEstimate'] ?? 'N/A') . "\n";
            echo "  EPS Actual: " . ($ryData['epsActual'] ?? 'N/A') . "\n";
            echo "  Revenue Estimate: " . ($ryData['revenueEstimate'] ?? 'N/A') . "\n";
            echo "  Revenue Actual: " . ($ryData['revenueActual'] ?? 'N/A') . "\n";
            echo "  Time: " . ($ryData['time'] ?? 'N/A') . "\n";
        } else {
            echo "❌ RY not found in today's earnings\n";
        }
        
        // Show all today's earnings for reference
        echo "\nAll today's earnings:\n";
        foreach ($response['earningsCalendar'] as $earning) {
            echo "  {$earning['symbol']}: EPS Est={$earning['epsEstimate']}, EPS Act={$earning['epsActual']}, Rev Est={$earning['revenueEstimate']}, Rev Act={$earning['revenueActual']}\n";
        }
        
    } else {
        echo "❌ No earnings calendar data\n";
        if (isset($response['error'])) {
            echo "Error: {$response['error']}\n";
        }
    }
    
    // Test 2: Company Profile
    echo "\n\n=== TEST 2: COMPANY PROFILE ===\n";
    $response = Finnhub::get('/stock/profile2', ['symbol' => $ticker]);
    
    if (isset($response['name'])) {
        echo "✅ Found company profile\n";
        echo "Name: {$response['name']}\n";
        echo "Country: {$response['country']}\n";
        echo "Currency: {$response['currency']}\n";
        echo "Exchange: {$response['exchange']}\n";
        echo "Market Cap: {$response['marketCapitalization']}\n";
        echo "Industry: {$response['finnhubIndustry']}\n";
        echo "Shares Outstanding: {$response['shareOutstanding']}\n";
    } else {
        echo "❌ No company profile data\n";
        if (isset($response['error'])) {
            echo "Error: {$response['error']}\n";
        }
    }
    
    // Test 3: Company Name (using existing method)
    echo "\n\n=== TEST 3: COMPANY NAME ===\n";
    $companyName = Finnhub::getCompanyName($ticker);
    if ($companyName) {
        echo "✅ Company Name: {$companyName}\n";
    } else {
        echo "❌ Could not get company name\n";
    }
    
    // Test 4: Shares Outstanding (using existing method)
    echo "\n\n=== TEST 4: SHARES OUTSTANDING ===\n";
    $sharesOutstanding = Finnhub::getSharesOutstanding($ticker);
    if ($sharesOutstanding) {
        echo "✅ Shares Outstanding: {$sharesOutstanding}\n";
    } else {
        echo "❌ Could not get shares outstanding\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n\n=== SUMMARY ===\n";
echo "Finnhub poskytuje pre RY:\n";
echo "1. EARNINGS_CALENDAR: Today's earnings with actual/estimate data\n";
echo "2. COMPANY_PROFILE: Company information\n";
echo "3. Rate limiting: 60 calls per minute (free tier)\n";
echo "\n=== COMPLETED ===\n";
?>
