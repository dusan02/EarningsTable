<?php
echo "=== DASHBOARD CHECK ===\n\n";

$url = 'http://localhost/dashboard-fixed.html';
$context = stream_context_create([
    'http' => [
        'timeout' => 5,
        'method' => 'GET'
    ]
]);

echo "Checking dashboard at: {$url}\n";

$response = @file_get_contents($url, false, $context);

if ($response !== false) {
    echo "✅ Dashboard is accessible\n";
    echo "Response length: " . strlen($response) . " characters\n";
    
    // Check if it contains earnings data
    if (strpos($response, 'earnings') !== false) {
        echo "✅ Dashboard contains earnings data\n";
    } else {
        echo "❌ Dashboard doesn't contain earnings data\n";
    }
    
    // Check if it contains market data
    if (strpos($response, 'market') !== false || strpos($response, 'price') !== false) {
        echo "✅ Dashboard contains market data\n";
    } else {
        echo "❌ Dashboard doesn't contain market data\n";
    }
} else {
    echo "❌ Dashboard is not accessible\n";
    echo "Error: " . error_get_last()['message'] ?? 'Unknown error' . "\n";
}

echo "\n=== API ENDPOINT CHECK ===\n";

// Check API endpoint
$apiUrl = 'http://localhost/api/today-earnings-movements.php';
echo "Checking API at: {$apiUrl}\n";

$apiResponse = @file_get_contents($apiUrl, false, $context);

if ($apiResponse !== false) {
    echo "✅ API is accessible\n";
    $data = json_decode($apiResponse, true);
    
    if ($data && isset($data['data'])) {
        echo "✅ API returns data\n";
        echo "Records count: " . count($data['data']) . "\n";
        
        // Check first record for market data
        if (!empty($data['data'])) {
            $firstRecord = $data['data'][0];
            echo "First record ticker: " . ($firstRecord['ticker'] ?? 'N/A') . "\n";
            echo "First record price: " . ($firstRecord['current_price'] ?? 'N/A') . "\n";
            echo "First record market cap: " . ($firstRecord['market_cap'] ?? 'N/A') . "\n";
        }
    } else {
        echo "❌ API doesn't return valid data\n";
    }
} else {
    echo "❌ API is not accessible\n";
}
?>
