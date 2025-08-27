<?php
require_once 'config.php';

echo "=== WELNF TICKER DATA ===\n";

$stmt = $pdo->query("SELECT * FROM TodayEarningsMovements WHERE ticker = 'WELNF'");
$row = $stmt->fetch();

if ($row) {
    echo "Found WELNF in TodayEarningsMovements:\n";
    print_r($row);
} else {
    echo "WELNF not found in TodayEarningsMovements\n";
}

echo "\n=== WELNF IN EARNINGSTICKERSTODAY ===\n";
$stmt = $pdo->query("SELECT * FROM EarningsTickersToday WHERE ticker = 'WELNF'");
$row = $stmt->fetch();

if ($row) {
    echo "Found WELNF in EarningsTickersToday:\n";
    print_r($row);
} else {
    echo "WELNF not found in EarningsTickersToday\n";
}

echo "\n=== TRYING TO FETCH WELNF FROM POLYGON ===\n";
// Try to fetch WELNF data manually
$apiKey = 'YOUR_POLYGON_API_KEY'; // Replace with actual key
$url = "https://api.polygon.io/v2/snapshot/locale/us/markets/stocks/tickers/WELNF?apikey=" . $apiKey;

echo "API URL: " . $url . "\n";
echo "Note: This would require a valid Polygon API key\n";
?>
