<?php
require_once 'config.php';

echo "=== TESTING POLYGON API RESPONSE ===\n\n";

// Test with a few known tickers
$testTickers = ['AAPL', 'MSFT', 'GOOGL', 'TSLA', 'META'];

$apiKey = POLYGON_API_KEY;
$tickerList = implode(',', $testTickers);

$url = "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers?tickers={$tickerList}&apiKey={$apiKey}";

echo "URL: {$url}\n\n";

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

if ($response === false) {
    echo "❌ Failed to get response\n";
    exit(1);
}

$data = json_decode($response, true);

if (!$data) {
    echo "❌ Failed to decode JSON\n";
    exit(1);
}

echo "Response structure:\n";
echo "Keys: " . implode(', ', array_keys($data)) . "\n\n";

if (isset($data['results'])) {
    echo "Found " . count($data['results']) . " results\n\n";
    
    foreach ($data['results'] as $index => $result) {
        echo "=== RESULT #" . ($index + 1) . " ===\n";
        echo "Keys: " . implode(', ', array_keys($result)) . "\n";
        echo "Ticker: " . ($result['ticker'] ?? 'N/A') . "\n";
        
        if (isset($result['lastTrade'])) {
            echo "Last Trade: " . json_encode($result['lastTrade']) . "\n";
        }
        
        if (isset($result['last'])) {
            echo "Last: " . json_encode($result['last']) . "\n";
        }
        
        if (isset($result['prevDay'])) {
            echo "Prev Day: " . json_encode($result['prevDay']) . "\n";
        }
        
        if (isset($result['marketCap'])) {
            echo "Market Cap: " . $result['marketCap'] . "\n";
        } else {
            echo "Market Cap: NOT FOUND\n";
        }
        
        if (isset($result['name'])) {
            echo "Name: " . $result['name'] . "\n";
        }
        
        echo "\n";
    }
} else {
    echo "No 'results' key found\n";
    echo "Full response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
}
?>
