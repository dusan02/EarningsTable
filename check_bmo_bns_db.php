<?php
require_once 'config.php';

echo "=== CHECKING BMO.TO AND BNS.TO IN DATABASE ===\n";

// Check EarningsTickersToday
echo "=== EARNINGS TICKERS TODAY ===\n";
$stmt = $pdo->prepare("SELECT * FROM earningstickerstoday WHERE ticker IN ('BMO.TO', 'BNS.TO')");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "❌ BMO.TO and BNS.TO not found in EarningsTickersToday\n";
} else {
    echo "✅ Found " . count($results) . " records:\n";
    foreach ($results as $row) {
        print_r($row);
    }
}

// Check TodayEarningsMovements
echo "\n=== TODAY EARNINGS MOVEMENTS ===\n";
$stmt = $pdo->prepare("SELECT * FROM todayearningsmovements WHERE ticker IN ('BMO.TO', 'BNS.TO')");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($results)) {
    echo "❌ BMO.TO and BNS.TO not found in TodayEarningsMovements\n";
} else {
    echo "✅ Found " . count($results) . " records:\n";
    foreach ($results as $row) {
        print_r($row);
    }
}

// Check all tickers that contain 'BMO' or 'BNS'
echo "\n=== SEARCHING FOR ANY BMO/BNS RELATED TICKERS ===\n";
$stmt = $pdo->prepare("SELECT ticker FROM earningstickerstoday WHERE ticker LIKE '%BMO%' OR ticker LIKE '%BNS%'");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_COLUMN);

if (empty($results)) {
    echo "❌ No BMO or BNS related tickers found\n";
} else {
    echo "✅ Found tickers: " . implode(', ', $results) . "\n";
}
?>
