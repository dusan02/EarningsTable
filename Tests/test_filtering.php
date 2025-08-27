<?php
echo "=== FILTERING TEST ===\n\n";

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
        $allRecords = $data['data'];
        echo "Total records: " . count($allRecords) . "\n\n";
        
        // Apply the same filtering as dashboard
        $filteredRecords = array_filter($allRecords, function($item) {
            $hasMarketCap = $item['market_cap'] && floatval($item['market_cap']) > 0;
            $hasPrice = $item['current_price'] && floatval($item['current_price']) > 0;
            return $hasMarketCap && $hasPrice;
        });
        
        echo "Records after filtering (with market cap AND price): " . count($filteredRecords) . "\n\n";
        
        // Show statistics
        $withMarketCap = 0;
        $withPrice = 0;
        $withBoth = 0;
        
        foreach ($allRecords as $record) {
            $hasMarketCap = $record['market_cap'] && floatval($record['market_cap']) > 0;
            $hasPrice = $record['current_price'] && floatval($record['current_price']) > 0;
            
            if ($hasMarketCap) $withMarketCap++;
            if ($hasPrice) $withPrice++;
            if ($hasMarketCap && $hasPrice) $withBoth++;
        }
        
        echo "=== STATISTICS ===\n";
        echo "Records with market cap: {$withMarketCap}\n";
        echo "Records with price: {$withPrice}\n";
        echo "Records with BOTH (will be shown): {$withBoth}\n";
        echo "Records that will be HIDDEN: " . (count($allRecords) - $withBoth) . "\n\n";
        
        // Show sample of hidden records
        echo "=== SAMPLE HIDDEN RECORDS ===\n";
        $hiddenCount = 0;
        foreach ($allRecords as $record) {
            $hasMarketCap = $record['market_cap'] && floatval($record['market_cap']) > 0;
            $hasPrice = $record['current_price'] && floatval($record['current_price']) > 0;
            
            if (!($hasMarketCap && $hasPrice)) {
                echo sprintf("%-6s | Market Cap: %-12s | Price: %-8s | Reason: %s\n",
                    $record['ticker'],
                    $record['market_cap'] ?: 'NULL',
                    $record['current_price'] ?: 'NULL',
                    !$hasMarketCap && !$hasPrice ? 'No market cap & no price' : 
                    (!$hasMarketCap ? 'No market cap' : 'No price')
                );
                $hiddenCount++;
                if ($hiddenCount >= 5) break;
            }
        }
        
        // Show sample of visible records
        echo "\n=== SAMPLE VISIBLE RECORDS ===\n";
        $visibleCount = 0;
        foreach ($allRecords as $record) {
            $hasMarketCap = $record['market_cap'] && floatval($record['market_cap']) > 0;
            $hasPrice = $record['current_price'] && floatval($record['current_price']) > 0;
            
            if ($hasMarketCap && $hasPrice) {
                echo sprintf("%-6s | Market Cap: %-12s | Price: %-8s | Market Cap Diff: %-12s\n",
                    $record['ticker'],
                    $record['market_cap'] ?: 'NULL',
                    $record['current_price'] ?: 'NULL',
                    $record['market_cap_diff'] ?: 'NULL'
                );
                $visibleCount++;
                if ($visibleCount >= 5) break;
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
