<?php
require_once 'config.php';

echo "=== POLYGON MARKET CAP TEST (FIXED) ===\n\n";

// Test Polygon ticker details API
$testTickers = ['AAPL', 'MSFT', 'GOOGL', 'PDD', 'VNET'];

foreach ($testTickers as $ticker) {
    echo "Testing {$ticker}...\n";
    
    $apiKey = POLYGON_API_KEY;
    $url = "https://api.polygon.io/v3/reference/tickers/{$ticker}?apikey={$apiKey}";
    
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
    
    $json = file_get_contents($url, false, $context);
    
    if ($json === false) {
        echo "❌ Failed to fetch data for {$ticker}\n";
    } else {
        $data = json_decode($json, true);
        
        if (isset($data['results'])) {
            $result = $data['results'];
            $marketCap = $result['market_cap'] ?? 'NULL';
            $name = $result['name'] ?? 'NULL';
            $sharesOutstanding = $result['shares_outstanding'] ?? 'NULL';
            
            echo "✅ {$ticker} - Name: {$name}\n";
            echo "   Market Cap: {$marketCap}\n";
            echo "   Shares Outstanding: {$sharesOutstanding}\n";
            
            // Show full response structure
            echo "   Full response keys: " . implode(', ', array_keys($result)) . "\n";
        } else {
            echo "❌ No results for {$ticker}\n";
            echo "Response: " . substr($json, 0, 200) . "...\n";
        }
    }
    
    echo "\n";
    sleep(1); // Rate limiting
}

echo "=== API KEY CHECK ===\n";
echo "POLYGON_API_KEY: " . substr(POLYGON_API_KEY, 0, 10) . "...\n";
?>
