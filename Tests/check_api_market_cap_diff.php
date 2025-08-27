<?php
echo "=== API MARKET CAP DIFF CHECK ===\n\n";

$apiUrl = 'http://localhost/api/today-earnings-movements.php';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

echo "Fetching data from API...\n";
$apiResponse = @file_get_contents($apiUrl, false, $context);

if ($apiResponse !== false) {
    $data = json_decode($apiResponse, true);
    
    if ($data && isset($data['data'])) {
        echo "✅ API returns data\n";
        echo "Records count: " . count($data['data']) . "\n\n";
        
        // Check first few records for market_cap_diff
        $recordsWithDiff = 0;
        foreach (array_slice($data['data'], 0, 10) as $record) {
            $ticker = $record['ticker'] ?? 'N/A';
            $marketCapDiff = $record['market_cap_diff'] ?? 'N/A';
            $priceChangePercent = $record['price_change_percent'] ?? 'N/A';
            $marketCap = $record['market_cap'] ?? 'N/A';
            
            echo sprintf("%-6s | Market Cap Diff: %-15s | Price Change: %-8s | Market Cap: %-12s\n",
                $ticker,
                $marketCapDiff,
                $priceChangePercent,
                $marketCap
            );
            
            if ($marketCapDiff !== 'N/A' && $marketCapDiff != 0) {
                $recordsWithDiff++;
            }
        }
        
        echo "\nRecords with market_cap_diff: {$recordsWithDiff}\n";
        
        // Show sample calculation
        if (!empty($data['data'])) {
            $firstRecord = $data['data'][0];
            $ticker = $firstRecord['ticker'];
            $priceChangePercent = $firstRecord['price_change_percent'];
            $marketCap = $firstRecord['market_cap'];
            $marketCapDiff = $firstRecord['market_cap_diff'];
            
            echo "\n=== SAMPLE CALCULATION ===\n";
            echo "Ticker: {$ticker}\n";
            echo "Price change: {$priceChangePercent}%\n";
            echo "Market cap: {$marketCap}\n";
            echo "API market_cap_diff: {$marketCapDiff}\n";
            
            if ($priceChangePercent && $marketCap && $marketCap > 0) {
                $calculatedDiff = ($priceChangePercent / 100) * $marketCap;
                echo "Calculated diff: {$calculatedDiff}\n";
                echo "Match: " . (abs($marketCapDiff - $calculatedDiff) < 0.01 ? "✅" : "❌") . "\n";
            }
        }
    } else {
        echo "❌ API doesn't return valid data\n";
        echo "Response: " . substr($apiResponse, 0, 500) . "...\n";
    }
} else {
    echo "❌ API is not accessible\n";
}
?>
