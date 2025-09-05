<?php
require_once 'config.php';

echo "=== CHECKING PRICES FROM DATABASE ===\n";
echo "Date: " . date('Y-m-d') . "\n\n";

$stmt = $pdo->prepare('SELECT ticker, price, price_change_percent FROM todayearningsmovements WHERE report_date = ? ORDER BY ticker');
$stmt->execute([date('Y-m-d')]);
$results = $stmt->fetchAll();

echo "Tickers with price issues:\n";
$problematic = 0;
$working = 0;

foreach ($results as $row) {
    if ($row['price'] == 0 || $row['price'] == null) {
        echo "❌ {$row['ticker']}: Price = {$row['price']}, Change = {$row['price_change_percent']}%\n";
        $problematic++;
    } else {
        echo "✅ {$row['ticker']}: Price = {$row['price']}, Change = {$row['price_change_percent']}%\n";
        $working++;
    }
}

echo "\n=== SUMMARY ===\n";
echo "✅ Working tickers: $working\n";
echo "❌ Problematic tickers: $problematic\n";
echo "📊 Total tickers: " . count($results) . "\n";

// Check which tickers are missing prices
echo "\n=== PROBLEMATIC TICKERS ===\n";
$stmt = $pdo->prepare('SELECT ticker FROM todayearningsmovements WHERE report_date = ? AND (price = 0 OR price IS NULL)');
$stmt->execute([date('Y-m-d')]);
$missing = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($missing as $ticker) {
    echo "🔍 Testing ticker: $ticker\n";
    
    // Test Polygon API for this ticker
    $apiKey = POLYGON_API_KEY;
    $url = "https://api.polygon.io/v2/aggs/ticker/$ticker/prev?adjusted=true&apikey=$apiKey";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (isset($data['results']) && count($data['results']) > 0) {
            $result = $data['results'][0];
            echo "  ✅ API Response: Price = {$result['c']}, Change = " . round((($result['c'] - $result['o']) / $result['o']) * 100, 2) . "%\n";
        } else {
            echo "  ❌ API Response: No data in results\n";
        }
    } else {
        echo "  ❌ API Response: HTTP $httpCode\n";
    }
    
    echo "\n";
}
?>
