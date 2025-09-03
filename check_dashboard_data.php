<?php
require_once 'config.php';

echo "=== CHECKING DASHBOARD COLUMNS DATA ===\n\n";

// Check specific columns that should appear in dashboard
$stmt = $pdo->prepare("
    SELECT ticker, 
           current_price, 
           previous_close, 
           price_change_percent,
           market_cap,
           market_cap_diff,
           market_cap_diff_billions
    FROM TodayEarningsMovements 
    LIMIT 10
");
$stmt->execute();
$results = $stmt->fetchAll();

foreach ($results as $row) {
    echo "Ticker: {$row['ticker']}\n";
    echo "  Current Price: {$row['current_price']}\n";
    echo "  Previous Close: {$row['previous_close']}\n";
    echo "  Change %: {$row['price_change_percent']}\n";
    echo "  Market Cap: {$row['market_cap']}\n";
    echo "  Market Cap Diff: {$row['market_cap_diff']}\n";
    echo "  Market Cap Diff Billions: {$row['market_cap_diff_billions']}\n";
    echo "---\n";
}

echo "\n=== CHECKING API RESPONSE ===\n";
echo "Testing if API returns the correct data structure...\n";

// Test API endpoint
$apiUrl = "http://localhost:8000/api/earnings-tickers-today.php";
echo "API URL: {$apiUrl}\n";

// Check if server is running
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $data = json_decode($response, true);
    if ($data && isset($data['data']) && count($data['data']) > 0) {
        $firstItem = $data['data'][0];
        echo "✅ API Response OK\n";
        echo "First ticker: {$firstItem['ticker']}\n";
        echo "Has current_price: " . (isset($firstItem['current_price']) ? 'Yes' : 'No') . "\n";
        echo "Has price_change_percent: " . (isset($firstItem['price_change_percent']) ? 'Yes' : 'No') . "\n";
        echo "Has market_cap_diff: " . (isset($firstItem['market_cap_diff']) ? 'Yes' : 'No') . "\n";
    } else {
        echo "❌ API Response invalid\n";
    }
} else {
    echo "❌ API Request failed: HTTP {$httpCode}\n";
}
?>
