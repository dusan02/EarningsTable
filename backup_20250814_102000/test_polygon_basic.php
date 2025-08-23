<?php
$polygonApiKey = 'Vi_pMLcusE8RA_SUvkPAmiyziVzlmOoX';

echo "🔍 TESTING POLYGON API CONNECTION\n\n";

// Test basic Polygon API
$testUrl = "https://api.polygon.io/v2/aggs/ticker/AAPL/range/1/day/2025-08-12/2025-08-12?apiKey=$polygonApiKey";

echo "🌐 Testing basic API call...\n";
$response = file_get_contents($testUrl);

if ($response === false) {
    echo "❌ Error connecting to Polygon API\n";
    exit;
}

$data = json_decode($response, true);

if (isset($data['results'])) {
    echo "✅ Polygon API connection successful!\n";
    echo "📊 API Key is valid\n";
    
    // Test earnings endpoints
    echo "\n🔍 TESTING EARNINGS ENDPOINTS:\n";
    
    $endpoints = [
        "Earnings Calendar" => "https://api.polygon.io/v2/reference/earnings?date=2025-08-12&apiKey=$polygonApiKey",
        "Earnings Surprises" => "https://api.polygon.io/v2/reference/earnings/surprises?date=2025-08-12&apiKey=$polygonApiKey",
        "Company Earnings" => "https://api.polygon.io/v2/reference/earnings/AAPL?apiKey=$polygonApiKey"
    ];
    
    foreach ($endpoints as $name => $url) {
        echo "\n📋 Testing: $name\n";
        $earningsResponse = file_get_contents($url);
        
        if ($earningsResponse === false) {
            echo "❌ Failed: $name\n";
        } else {
            $earningsData = json_decode($earningsResponse, true);
            if (isset($earningsData['results'])) {
                echo "✅ Success: $name - " . count($earningsData['results']) . " results\n";
            } else {
                echo "⚠️  No results: $name\n";
                echo "Response: " . substr($earningsResponse, 0, 100) . "...\n";
            }
        }
    }
    
} else {
    echo "❌ Invalid API response\n";
    echo "Response: " . substr($response, 0, 200) . "...\n";
}
?>
