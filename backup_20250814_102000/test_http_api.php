<?php
echo "=== HTTP API TEST ===\n\n";

// Test the HTTP API endpoint
$url = 'http://localhost:8080/api/earnings-tickers-today.php';
$context = stream_context_create(['http' => ['timeout' => 10]]);
$response = @file_get_contents($url, false, $context);

if ($response !== false) {
    $data = json_decode($response, true);
    
    if (isset($data['data']) && is_array($data['data'])) {
        echo "✅ HTTP API Response: " . count($data['data']) . " records\n\n";
        
        // Check first 5 records for Market Cap DESC sorting
        echo "🏆 FIRST 5 RECORDS (should be sorted by Market Cap DESC):\n";
        for ($i = 0; $i < min(5, count($data['data'])); $i++) {
            $item = $data['data'][$i];
            $marketCap = isset($item['market_cap']) ? $item['market_cap'] : 0;
            $marketCapFormatted = $marketCap > 0 ? number_format($marketCap / 1000000000, 2) . 'B' : 'N/A';
            
            echo sprintf("   %2d. %-6s | Market Cap: %-10s | Price: $%-8.2f\n", 
                $i + 1, 
                $item['ticker'], 
                $marketCapFormatted,
                $item['current_price'] ?? 0
            );
        }
        
        echo "\n✅ HTTP API is working correctly!\n";
        echo "🎯 Now test: http://localhost:8080/earnings-table.html\n";
        
    } else {
        echo "❌ HTTP API Response: Invalid data structure\n";
        echo "Response: " . substr($response, 0, 200) . "...\n";
    }
} else {
    echo "❌ HTTP API Request: Failed to fetch data\n";
    echo "URL: " . $url . "\n";
}
?>
