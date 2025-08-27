<?php
require_once 'config.php';

echo "=== TESTING POLYGON API STRUCTURE ===\n";

// Test with a few known tickers
$testTickers = ['PANW', 'FUTU', 'API'];
$symbols = implode(',', $testTickers);
$url = "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers?tickers={$symbols}&apiKey=Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX";

echo "Testing with tickers: " . implode(', ', $testTickers) . "\n";
echo "URL: " . $url . "\n\n";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: Polygon-Test/1.0'
        ]
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ API call failed\n";
    exit(1);
}

$data = json_decode($response, true);

if (!$data) {
    echo "❌ Invalid JSON response\n";
    exit(1);
}

echo "=== FULL API RESPONSE STRUCTURE ===\n";
echo json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

echo "=== ANALYZING FIRST TICKER ===\n";
if (isset($data['tickers']) && !empty($data['tickers'])) {
    $firstTicker = $data['tickers'][0];
    echo "First ticker: " . $firstTicker['ticker'] . "\n";
    echo "Available keys: " . implode(', ', array_keys($firstTicker)) . "\n\n";
    
    foreach ($firstTicker as $key => $value) {
        if (is_array($value)) {
            echo "{$key}: " . json_encode($value) . "\n";
        } else {
            echo "{$key}: {$value}\n";
        }
    }
}
?>
