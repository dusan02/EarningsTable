<?php
require_once 'config.php';

echo "=== TESTING YAHOO FINANCE MARKET CAP ===\n";

function testYahooEndpoint($ticker, $endpoint) {
    $url = "https://query1.finance.yahoo.com{$endpoint}";
    $url = str_replace('{ticker}', $ticker, $url);
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return null;
    }
    
    return json_decode($response, true);
}

$testTicker = 'BHP';

echo "Testing endpoints for {$testTicker}:\n\n";

// Test different endpoints
$endpoints = [
    'Basic Quote' => "/v8/finance/chart/{ticker}",
    'Quote Summary' => "/v10/finance/quoteSummary/{ticker}?modules=defaultKeyStatistics",
    'Key Statistics' => "/v10/finance/quoteSummary/{ticker}?modules=defaultKeyStatistics,financialData",
    'Profile' => "/v10/finance/quoteSummary/{ticker}?modules=assetProfile"
];

foreach ($endpoints as $name => $endpoint) {
    echo "--- {$name} ---\n";
    $data = testYahooEndpoint($testTicker, $endpoint);
    
    if ($data) {
        echo "✅ Response received\n";
        
        // Look for market cap in different locations
        if (isset($data['chart']['result'][0]['meta']['marketCap'])) {
            $marketCap = $data['chart']['result'][0]['meta']['marketCap'];
            echo "  Market Cap: $" . number_format($marketCap / 1000000000, 1) . "B\n";
        } elseif (isset($data['quoteSummary']['result'][0]['defaultKeyStatistics']['marketCap'])) {
            $marketCap = $data['quoteSummary']['result'][0]['defaultKeyStatistics']['marketCap']['raw'];
            echo "  Market Cap: $" . number_format($marketCap / 1000000000, 1) . "B\n";
        } else {
            echo "  Market Cap: Not found\n";
        }
        
        // Show first few keys for debugging
        $keys = array_keys($data);
        echo "  Top level keys: " . implode(', ', array_slice($keys, 0, 5)) . "\n";
        
    } else {
        echo "❌ No response\n";
    }
    
    echo "\n";
    sleep(1);
}

echo "=== TEST COMPLETE ===\n";
?>
