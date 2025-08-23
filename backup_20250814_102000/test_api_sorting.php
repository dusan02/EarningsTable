<?php
require_once 'config.php';

echo "=== API SORTING TEST ===\n\n";

// Test the main API endpoint
$url = 'http://localhost/earnings-table/public/api/earnings-tickers-today.php';
$context = stream_context_create(['http' => ['timeout' => 10]]);
$response = @file_get_contents($url, false, $context);

if ($response !== false) {
    $data = json_decode($response, true);
    
    if (isset($data['data']) && is_array($data['data'])) {
        echo "✅ API Response: " . count($data['data']) . " records\n\n";
        
        // Check first 10 records for Market Cap DESC sorting
        echo "🏆 FIRST 10 RECORDS (should be sorted by Market Cap DESC):\n";
        for ($i = 0; $i < min(10, count($data['data'])); $i++) {
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
        
        // Verify sorting
        $isSorted = true;
        for ($i = 1; $i < min(10, count($data['data'])); $i++) {
            $prevMC = $data['data'][$i-1]['market_cap'] ?? 0;
            $currMC = $data['data'][$i]['market_cap'] ?? 0;
            
            if ($prevMC < $currMC && $prevMC > 0 && $currMC > 0) {
                $isSorted = false;
                break;
            }
        }
        
        echo "\n" . ($isSorted ? "✅" : "❌") . " SORTING VERIFICATION: " . 
             ($isSorted ? "Data is correctly sorted by Market Cap DESC" : "Data is NOT sorted correctly") . "\n";
        
    } else {
        echo "❌ API Response: Invalid data structure\n";
    }
} else {
    echo "❌ API Request: Failed to fetch data\n";
}

echo "\n🎯 FRONTEND TEST:\n";
echo "   Test the table at: http://localhost/earnings-table/public/earnings-table.html\n";
echo "   Verify that companies are sorted by Market Cap (largest first)\n";
echo "   Check that prices are displayed correctly (not 0.00)\n";
echo "   Verify price change percentages are reasonable (not -100%)\n";
?>
