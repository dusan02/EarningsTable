<?php
echo "=== API ENDPOINTS CHECK ===\n\n";

$endpoints = [
    'today-earnings-movements.php',
    'earnings-tickers-today.php',
    'earnings-tickers-today-fixed.php',
    'earnings-tickers-today-working.php'
];

foreach ($endpoints as $endpoint) {
    $apiUrl = "http://localhost/api/{$endpoint}";
    echo "Testing: {$endpoint}\n";
    echo "URL: {$apiUrl}\n";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'method' => 'GET'
        ]
    ]);
    
    $apiResponse = @file_get_contents($apiUrl, false, $context);
    
    if ($apiResponse !== false) {
        $data = json_decode($apiResponse, true);
        
        if ($data && isset($data['data'])) {
            echo "✅ Works - Records: " . count($data['data']) . "\n";
            
            // Check if it has market_cap_diff
            if (!empty($data['data'])) {
                $firstRecord = $data['data'][0];
                $hasMarketCapDiff = isset($firstRecord['market_cap_diff']);
                echo "   Has market_cap_diff: " . ($hasMarketCapDiff ? "✅" : "❌") . "\n";
                
                if ($hasMarketCapDiff) {
                    $marketCapDiff = $firstRecord['market_cap_diff'];
                    echo "   Sample market_cap_diff: {$marketCapDiff}\n";
                }
            }
        } else {
            echo "❌ Invalid JSON response\n";
        }
    } else {
        echo "❌ Not accessible\n";
    }
    
    echo "\n";
}
?>
