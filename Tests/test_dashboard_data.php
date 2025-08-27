<?php
echo "=== DASHBOARD DATA TEST ===\n\n";

// Test the API endpoint that dashboard uses
$apiUrl = 'http://localhost/api/earnings-tickers-today.php';
$context = stream_context_create([
    'http' => [
        'timeout' => 10,
        'method' => 'GET'
    ]
]);

echo "Fetching data from dashboard API...\n";
$apiResponse = @file_get_contents($apiUrl, false, $context);

if ($apiResponse !== false) {
    $data = json_decode($apiResponse, true);
    
    if ($data && isset($data['data'])) {
        echo "✅ API returns data\n";
        echo "Records count: " . count($data['data']) . "\n\n";
        
        // Check first few records for market_cap_diff
        $recordsWithDiff = 0;
        foreach (array_slice($data['data'], 0, 5) as $record) {
            $ticker = $record['ticker'] ?? 'N/A';
            $marketCapDiff = $record['market_cap_diff'] ?? 'N/A';
            $priceChangePercent = $record['price_change_percent'] ?? 'N/A';
            $marketCap = $record['market_cap'] ?? 'N/A';
            
            echo "{$ticker}:\n";
            echo "  Market Cap Diff: {$marketCapDiff}\n";
            echo "  Price Change: {$priceChangePercent}%\n";
            echo "  Market Cap: {$marketCap}\n";
            
            if ($marketCapDiff !== 'N/A' && $marketCapDiff != 0) {
                $recordsWithDiff++;
                
                // Format like JavaScript would
                $num = floatval($marketCapDiff);
                $absNum = abs($num);
                $formatted = '';
                
                if ($absNum >= 1e12) $formatted = '$' . ($absNum / 1e12) . 'T';
                else if ($absNum >= 1e9) $formatted = '$' . ($absNum / 1e9) . 'B';
                else if ($absNum >= 1e6) $formatted = '$' . ($absNum / 1e6) . 'M';
                else if ($absNum >= 1e3) $formatted = '$' . ($absNum / 1e3) . 'K';
                else $formatted = '$' . $absNum;
                
                if ($num < 0) $formatted = '-' . $formatted;
                
                echo "  Formatted: {$formatted}\n";
            }
            echo "\n";
        }
        
        echo "Records with market_cap_diff: {$recordsWithDiff}\n";
        
    } else {
        echo "❌ API doesn't return valid data\n";
        echo "Response: " . substr($apiResponse, 0, 500) . "...\n";
    }
} else {
    echo "❌ API is not accessible\n";
}
?>
