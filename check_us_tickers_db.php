<?php
require_once 'config.php';

echo "=== CHECKING US TICKERS BMO AND BNS IN DATABASE ===\n";

// Check EarningsTickersToday for US tickers
echo "=== EARNINGS TICKERS TODAY ===\n";
$stmt = $pdo->prepare("SELECT * FROM earningstickerstoday WHERE ticker IN ('BMO', 'BNS')");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "❌ US BMO and BNS not found in EarningsTickersToday\n";
} else {
    echo "✅ Found " . count($results) . " US ticker records:\n";
    foreach ($results as $row) {
        print_r($row);
    }
}

// Check TodayEarningsMovements for US tickers
echo "\n=== TODAY EARNINGS MOVEMENTS ===\n";
$stmt = $pdo->prepare("SELECT * FROM todayearningsmovements WHERE ticker IN ('BMO', 'BNS')");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "❌ US BMO and BNS not found in TodayEarningsMovements\n";
} else {
    echo "✅ Found " . count($results) . " US ticker records:\n";
    foreach ($results as $row) {
        print_r($row);
    }
}

// Check if we can get US ticker data from Polygon
echo "\n=== TESTING POLYGON API FOR US TICKERS ===\n";
require_once 'common/api_functions.php';

$usTickers = ['BMO', 'BNS'];
foreach ($usTickers as $ticker) {
    echo "\n--- Testing US {$ticker} ---\n";
    
    // Test ticker details
    $details = getPolygonTickerDetails($ticker);
    if ($details) {
        echo "✅ US {$ticker} details found\n";
        echo "Market cap: " . ($details['market_cap'] ?? 'N/A') . "\n";
        echo "Name: " . ($details['name'] ?? 'N/A') . "\n";
    } else {
        echo "❌ US {$ticker} details not found\n";
    }
    
    // Test batch quote
    $batchData = getPolygonBatchQuote([$ticker]);
    if ($batchData && isset($batchData[$ticker])) {
        echo "✅ US {$ticker} batch quote found\n";
        $currentPrice = getCurrentPrice($batchData[$ticker]);
        echo "Current price: " . ($currentPrice ?? 'N/A') . "\n";
    } else {
        echo "❌ US {$ticker} batch quote not found\n";
    }
}
?>
