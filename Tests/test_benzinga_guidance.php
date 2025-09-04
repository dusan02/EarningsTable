<?php
/**
 * Test Benzinga Guidance API pre aktuálne tickery
 */

require_once 'config.php';
require_once 'test_helper.php';

echo "🔍 Testing Benzinga Guidance API\n";
echo "================================\n\n";

// Načíta aktuálne tickery s guidance dátami
$testTickers = TestHelper::getTickersWithGuidance(3);
TestHelper::printTickerInfo($testTickers, "Testing Guidance API for");

foreach ($testTickers as $ticker) {
    echo "📊 Testing ticker: {$ticker}\n";
    echo "--------------------------------\n";
    
    // URL pre Benzinga Guidance API cez Polygon
    $url = 'https://api.polygon.io/benzinga/v1/guidance';
    $params = [
        'apiKey' => POLYGON_API_KEY,
        'ticker' => $ticker,
        'limit' => 10,
        'sort' => 'date.desc'
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
            echo "   - Status: " . ($data['status'] ?? 'N/A') . "\n";
            echo "   - Request ID: " . ($data['request_id'] ?? 'N/A') . "\n";
            echo "   - Count: " . ($data['count'] ?? 'N/A') . "\n";
            
            if (isset($data['results']) && is_array($data['results'])) {
                $guidanceCount = count($data['results']);
                echo "   - Guidance records: {$guidanceCount}\n\n";
                
                if ($guidanceCount > 0) {
                    echo "📋 Guidance Data:\n";
                    foreach ($data['results'] as $index => $guidance) {
                        echo "   Record " . ($index + 1) . ":\n";
                        echo "     - Date: " . ($guidance['date'] ?? 'N/A') . "\n";
                        echo "     - Period: " . ($guidance['fiscal_period'] ?? 'N/A') . "\n";
                        echo "     - Year: " . ($guidance['fiscal_year'] ?? 'N/A') . "\n";
                        echo "     - EPS Guidance: " . ($guidance['estimated_eps_guidance'] ?? 'N/A') . "\n";
                        echo "     - Revenue Guidance: " . ($guidance['estimated_revenue_guidance'] ? number_format($guidance['estimated_revenue_guidance']) : 'N/A') . "\n";
                        echo "     - Currency: " . ($guidance['currency'] ?? 'N/A') . "\n";
                        echo "     - Company: " . ($guidance['company_name'] ?? 'N/A') . "\n";
                        echo "\n";
                    }
                } else {
                    echo "⚠️  No guidance records found\n";
                }
            } else {
                echo "⚠️  No 'results' field in response\n";
                echo "📄 Full response: " . json_encode($data, JSON_PRETTY_PRINT) . "\n";
            }
        } else {
            echo "❌ JSON decode error: " . json_last_error_msg() . "\n";
            echo "📄 Raw response: " . $response . "\n";
        }
    }
    
    curl_close($ch);
    echo "\n" . str_repeat("=", 50) . "\n\n";
}
?>
