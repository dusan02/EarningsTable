<?php
require_once 'config.php';

// Test tickers that show $0.00 in dashboard
$testTickers = ['BBN', 'QD', 'FOF', 'GGT', 'HURC', 'ZENV'];

echo "=== TESTING POLYGON API FOR PROBLEMATIC TICKERS ===\n";
echo "API Key: " . (POLYGON_API_KEY ? 'SET' : 'NOT SET') . "\n\n";

foreach ($testTickers as $ticker) {
    echo "🔍 Testing ticker: $ticker\n";
    
    // Test Polygon API for this ticker
    $apiKey = POLYGON_API_KEY;
    $url = "https://api.polygon.io/v2/aggs/ticker/$ticker/prev?adjusted=true&apikey=$apiKey";
    
    echo "  URL: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "  HTTP Code: $httpCode\n";
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        echo "  Response: " . substr($response, 0, 200) . "...\n";
        
        if (isset($data['results']) && count($data['results']) > 0) {
            $result = $data['results'][0];
            $price = $result['c'] ?? 'N/A';
            $open = $result['o'] ?? 'N/A';
            $change = $open > 0 ? round((($price - $open) / $open) * 100, 2) : 'N/A';
            echo "  ✅ Price: $price, Open: $open, Change: $change%\n";
        } else {
            echo "  ❌ No data in results\n";
        }
    } else {
        echo "  ❌ HTTP Error: $httpCode\n";
        echo "  Response: " . substr($response, 0, 200) . "...\n";
    }
    
    echo "\n";
}

// Check database for these tickers
echo "=== CHECKING DATABASE ===\n";
foreach ($testTickers as $ticker) {
    $stmt = $pdo->prepare('SELECT ticker, current_price, price_change_percent, previous_close FROM todayearningsmovements WHERE ticker = ? AND report_date = ?');
    $stmt->execute([$ticker, date('Y-m-d')]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo "📊 $ticker: Price={$row['current_price']}, Change={$row['price_change_percent']}%, Previous={$row['previous_close']}\n";
    } else {
        echo "❌ $ticker: Not found in database\n";
    }
}
?>
