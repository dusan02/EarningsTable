<?php
require_once 'config.php';

echo "=== ALPHA VANTAGE EPS & REVENUE DATA TEST ===\n";

// Alpha Vantage API key
$apiKey = 'YFO8D5S1D0E4F80C';
$baseUrl = 'https://www.alphavantage.co/query';

// Test with some key tickers from today's earnings
$testTickers = ['NVDA', 'BILL', 'SNOW', 'NTNX', 'HPQ', 'LULU', 'GAP'];

echo "Testing tickers: " . implode(', ', $testTickers) . "\n\n";

foreach ($testTickers as $ticker) {
    echo "=== TESTING {$ticker} ===\n";
    
    // Test 1: EARNINGS (Historical quarterly data)
    echo "1. EARNINGS (Historical Quarterly Data):\n";
    $url = "{$baseUrl}?function=EARNINGS&symbol={$ticker}&apikey={$apiKey}";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 30,
            'header' => [
                'User-Agent: EarningsTable/1.0',
                'Accept: application/json'
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);
    
    if (isset($data['quarterlyEarnings'])) {
        echo "✅ Found quarterly earnings data\n";
        $quarterly = $data['quarterlyEarnings'];
        echo "Total quarterly records: " . count($quarterly) . "\n";
        
        // Show latest 3 quarters
        echo "Latest 3 quarters:\n";
        for ($i = 0; $i < min(3, count($quarterly)); $i++) {
            $q = $quarterly[$i];
            echo "  {$q['fiscalDateEnding']}: EPS={$q['reportedEPS']}, Revenue={$q['reportedRevenue']}\n";
        }
    } else {
        echo "❌ No quarterly earnings data\n";
        if (isset($data['Note'])) {
            echo "API Note: {$data['Note']}\n";
        }
    }
    
    // Rate limiting - Alpha Vantage allows 5 calls per minute
    sleep(12);
    
    // Test 2: EARNINGS_CALENDAR (Future estimates)
    echo "\n2. EARNINGS_CALENDAR (Future Estimates):\n";
    $url = "{$baseUrl}?function=EARNINGS_CALENDAR&symbol={$ticker}&horizon=3month&apikey={$apiKey}";
    
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);
    
    if (isset($data['earningsCalendar'])) {
        echo "✅ Found earnings calendar data\n";
        $calendar = $data['earningsCalendar'];
        echo "Total calendar records: " . count($calendar) . "\n";
        
        // Show next few earnings
        echo "Next earnings:\n";
        for ($i = 0; $i < min(3, count($calendar)); $i++) {
            $c = $calendar[$i];
            echo "  {$c['reportDate']}: EPS Est={$c['estimate']}, Revenue Est={$c['revenueEstimate']}\n";
        }
    } else {
        echo "❌ No earnings calendar data\n";
        if (isset($data['Note'])) {
            echo "API Note: {$data['Note']}\n";
        }
    }
    
    // Rate limiting
    sleep(12);
    
    // Test 3: INCOME_STATEMENT (Annual revenue data)
    echo "\n3. INCOME_STATEMENT (Annual Revenue Data):\n";
    $url = "{$baseUrl}?function=INCOME_STATEMENT&symbol={$ticker}&apikey={$apiKey}";
    
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);
    
    if (isset($data['annualReports'])) {
        echo "✅ Found annual income statements\n";
        $annual = $data['annualReports'];
        echo "Total annual records: " . count($annual) . "\n";
        
        // Show latest 2 years
        echo "Latest 2 years:\n";
        for ($i = 0; $i < min(2, count($annual)); $i++) {
            $a = $annual[$i];
            echo "  {$a['fiscalDateEnding']}: Revenue={$a['totalRevenue']}, Net Income={$a['netIncome']}\n";
        }
    } else {
        echo "❌ No income statement data\n";
        if (isset($data['Note'])) {
            echo "API Note: {$data['Note']}\n";
        }
    }
    
    // Rate limiting
    sleep(12);
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

echo "=== SUMMARY ===\n";
echo "Alpha Vantage poskytuje:\n";
echo "1. EARNINGS - Historické quarterly EPS a revenue dáta\n";
echo "2. EARNINGS_CALENDAR - Budúce EPS a revenue estimates\n";
echo "3. INCOME_STATEMENT - Ročné revenue a net income dáta\n";
echo "4. Rate limiting: 5 calls per minute\n";
echo "\n=== COMPLETED ===\n";
?>
