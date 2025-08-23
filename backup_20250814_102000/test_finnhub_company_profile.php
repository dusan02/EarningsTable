<?php
require_once 'config.php';

echo "🔍 Testing Finnhub Company Profile API\n";

// Test with a few known tickers
$tickers = ['AAPL', 'MSFT', 'GOOGL', 'CRWV', 'CAVA'];

foreach ($tickers as $ticker) {
    $url = "https://finnhub.io/api/v1/stock/profile2?symbol={$ticker}&token=" . FINNHUB_API_KEY;
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => ['Accept: application/json'],
            'timeout' => 10,
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        echo "❌ Failed to fetch data for {$ticker}\n";
        continue;
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "❌ JSON decode error for {$ticker}: " . json_last_error_msg() . "\n";
        continue;
    }
    
    if (isset($data['name'])) {
        echo "✅ {$ticker}: '{$data['name']}' (Country: {$data['country']}, Industry: {$data['finnhubIndustry']})\n";
    } else {
        echo "❌ {$ticker}: No company name found\n";
    }
    
    // Sleep to avoid rate limits
    sleep(1);
}
?>
