<?php
require_once 'config.php';

echo "=== TESTING API CONNECTIVITY ===\n";

// Test with file_get_contents
$testUrl = "https://api.polygon.io/v2/aggs/ticker/AAPL/prev?adjusted=true&apikey=" . POLYGON_API_KEY;

echo "Testing URL: " . substr($testUrl, 0, 100) . "...\n";

$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

$response = @file_get_contents($testUrl, false, $context);

if ($response === false) {
    echo "❌ file_get_contents failed\n";
    
    // Check if cURL is available
    if (function_exists('curl_init')) {
        echo "✅ cURL is available\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            echo "❌ cURL Error: $error\n";
        } else {
            echo "✅ cURL Response: HTTP $httpCode\n";
            if ($httpCode == 200) {
                $data = json_decode($response, true);
                if (isset($data['results'][0]['c'])) {
                    echo "✅ AAPL Price: {$data['results'][0]['c']}\n";
                }
            }
        }
    } else {
        echo "❌ cURL is not available\n";
    }
} else {
    echo "✅ file_get_contents success\n";
    $data = json_decode($response, true);
    if (isset($data['results'][0]['c'])) {
        echo "✅ AAPL Price: {$data['results'][0]['c']}\n";
    }
}

// Test problematic tickers
echo "\n=== TESTING PROBLEMATIC TICKERS ===\n";
$testTickers = ['BBN', 'QD', 'FOF', 'GGT', 'HURC', 'ZENV'];

foreach ($testTickers as $ticker) {
    $url = "https://api.polygon.io/v2/aggs/ticker/$ticker/prev?adjusted=true&apikey=" . POLYGON_API_KEY;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ $ticker: cURL Error: $error\n";
    } else {
        echo "📊 $ticker: HTTP $httpCode\n";
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if (isset($data['results']) && count($data['results']) > 0) {
                $result = $data['results'][0];
                echo "  ✅ Price: {$result['c']}, Open: {$result['o']}\n";
            } else {
                echo "  ❌ No data in results\n";
            }
        } else {
            echo "  ❌ HTTP Error: $httpCode\n";
        }
    }
}
?>
