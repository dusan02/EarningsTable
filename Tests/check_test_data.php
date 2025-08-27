<?php
require_once 'config.php';

echo "=== KONTROLA TESTOVACÍCH DÁT ===\n\n";

// Check for test data (big tech companies)
$stmt = $pdo->query("SELECT ticker, current_price, market_cap, price_change_percent FROM TodayEarningsMovements WHERE ticker IN ('AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA')");
$testData = $stmt->fetchAll();

echo "Testovacie dáta (Big Tech):\n";
foreach ($testData as $row) {
    echo "- {$row['ticker']}: \${$row['current_price']} (MC: " . ($row['market_cap'] ?: 'null') . ", Change: {$row['price_change_percent']}%)\n";
}

echo "\n=== VŠETKY DÁTA ===\n";
$stmt = $pdo->query("SELECT ticker, current_price, market_cap, price_change_percent FROM TodayEarningsMovements ORDER BY ticker");
$allData = $stmt->fetchAll();

echo "Celkovo záznamov: " . count($allData) . "\n\n";
foreach ($allData as $row) {
    echo "- {$row['ticker']}: \${$row['current_price']} (MC: " . ($row['market_cap'] ?: 'null') . ", Change: {$row['price_change_percent']}%)\n";
}
?>
