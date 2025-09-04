<?php
/**
 * Test Benzinga API priamo pre guidance data
 */

require_once 'config.php';
require_once 'test_helper.php';

echo "🔍 Testing Benzinga API Directly\n";
echo "================================\n\n";

// Načíta aktuálne tickery s guidance dátami
$testTickers = TestHelper::getTickersWithGuidance(3);
TestHelper::printTickerInfo($testTickers, "Testing Direct Benzinga API for");

foreach ($testTickers as $ticker) {
    echo "📊 Testing ticker: {$ticker}\n";
    echo "--------------------------------\n";
    
    // Priame volanie Benzinga API
    $url = 'https://api.benzinga.com/api/v2.1/guidance';
    $params = [
        'token' => BENZINGA_API_KEY,
        'tickers' => $ticker,
        'limit' => 10
    ];
    
    $fullUrl = $url . '?' . http_build_query($params);
    echo "🔗 API URL: " . $fullUrl . "\n\n";
    
    // Volanie API
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $fullUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: EarningsTable/1.0'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error) {
        echo "❌ cURL Error: " . $error . "\n";
    } elseif ($httpCode !== 200) {
        echo "❌ HTTP Error: {$httpCode}\n";
        echo "Response: " . $response . "\n";
    } else {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ API Response received successfully\n";
            echo "📊 Response structure:\n";
            
            // Výpis celej štruktúry
            echo "📄 Full response structure:\n";
            print_r($data);
            
        } else {
            echo "❌ JSON decode error: " . json_last_error_msg() . "\n";
            echo "📄 Raw response: " . $response . "\n";
        }
    }
    
    curl_close($ch);
    echo "\n" . str_repeat("=", 50) . "\n\n";
}

// Test aj cez Polygon s inými parametrami
echo "🔄 Testing Polygon Benzinga with different parameters\n";
echo "====================================================\n\n";

foreach ($testTickers as $ticker) {
    echo "📊 Testing ticker: {$ticker} via Polygon\n";
    echo "----------------------------------------\n";
    
    $url = 'https://api.polygon.io/benzinga/v1/guidance';
    $params = [
        'apiKey' => POLYGON_API_KEY,
        'ticker' => $ticker,
        'limit' => 5,
        'sort' => 'date.desc'
    ];
    
    $fullUrl = $url . '?' . http_build_query($params);
    echo "🔗 API URL: " . $fullUrl . "\n\n";
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $fullUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'User-Agent: EarningsTable/1.0'
        ]
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    if ($error) {
        echo "❌ cURL Error: " . $error . "\n";
    } elseif ($httpCode !== 200) {
        echo "❌ HTTP Error: {$httpCode}\n";
        echo "Response: " . $response . "\n";
    } else {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "✅ API Response received successfully\n";
            echo "📊 Response structure:\n";
            
            // Výpis celej štruktúry
            echo "📄 Full response structure:\n";
            print_r($data);
            
        } else {
            echo "❌ JSON decode error: " . json_last_error_msg() . "\n";
            echo "📄 Raw response: " . $response . "\n";
        }
    }
    
    curl_close($ch);
    echo "\n" . str_repeat("=", 50) . "\n\n";
}
?>
