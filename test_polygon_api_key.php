<?php
require_once 'config.php';

echo "=== POLYGON API KEY TEST ===\n";
echo "Polygon API Key: " . (defined('POLYGON_API_KEY') ? POLYGON_API_KEY : 'NOT DEFINED') . "\n";
echo "Finnhub API Key: " . (defined('FINNHUB_API_KEY') ? FINNHUB_API_KEY : 'NOT DEFINED') . "\n";

// Test Polygon API call
echo "\n=== TESTING POLYGON API ===\n";
$url = "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers?tickers=AAPL&apikey=" . POLYGON_API_KEY;

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'header' => [
            'User-Agent: EarningsTable/1.0',
            'Accept: application/json'
        ]
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ API call failed\n";
} else {
    $data = json_decode($response, true);
    if (isset($data['tickers']) && !empty($data['tickers'])) {
        echo "✅ API call successful!\n";
        echo "Response contains " . count($data['tickers']) . " tickers\n";
    } else {
        echo "❌ API response invalid: " . substr($response, 0, 200) . "...\n";
    }
}
?>
