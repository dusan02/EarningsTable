<?php
require_once 'config.php';
require_once 'common/Finnhub.php';

echo "=== FINNHUB SHARES OUTSTANDING TEST ===\n";

$testTicker = 'NVDA';

echo "Testing ticker: {$testTicker}\n\n";

// Get full profile data
$finnhub = new Finnhub();
$data = $finnhub->get('/stock/profile2', ['symbol' => $testTicker]);

if ($data) {
    echo "✅ Finnhub profile data received\n";
    echo "Raw response:\n";
    print_r($data);
    
    echo "\n=== KEY FIELDS ===\n";
    echo "Company Name: " . ($data['name'] ?? 'N/A') . "\n";
    echo "Shares Outstanding: " . ($data['shareOutstanding'] ?? 'N/A') . "\n";
    echo "Market Cap: " . ($data['marketCapitalization'] ?? 'N/A') . "\n";
    
    // Check if shares outstanding is in millions
    $shares = $data['shareOutstanding'] ?? null;
    if ($shares) {
        echo "\n=== SHARES ANALYSIS ===\n";
        echo "Raw shares: {$shares}\n";
        echo "Type: " . gettype($shares) . "\n";
        
        // Try different interpretations
        echo "As millions: " . ($shares * 1000000) . "\n";
        echo "As thousands: " . ($shares * 1000) . "\n";
        echo "As is: " . $shares . "\n";
    }
} else {
    echo "❌ Failed to get Finnhub data\n";
}
?>
