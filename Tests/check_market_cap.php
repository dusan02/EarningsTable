<?php
require_once 'config.php';

echo "=== KONTROLA MARKET CAP DÁT ===\n\n";

// Check PDD and TIGR market cap
$stmt = $pdo->query("SELECT ticker, current_price, market_cap, price_change_percent FROM TodayEarningsMovements WHERE ticker IN ('PDD', 'TIGR')");
$data = $stmt->fetchAll();

echo "PDD a TIGR market cap:\n";
foreach ($data as $row) {
    echo "- {$row['ticker']}: \${$row['current_price']} (MC: " . ($row['market_cap'] ?: 'null') . ", Change: {$row['price_change_percent']}%)\n";
}

echo "\n=== VŠETKY TICKERS S MARKET CAP ===\n";
$stmt = $pdo->query("SELECT ticker, current_price, market_cap FROM TodayEarningsMovements WHERE market_cap > 0 ORDER BY market_cap DESC");
$withMarketCap = $stmt->fetchAll();

echo "Tickers s market cap: " . count($withMarketCap) . "\n";
foreach ($withMarketCap as $row) {
    $marketCapBillions = $row['market_cap'] / 1000000000;
    echo "- {$row['ticker']}: \${$row['current_price']} (MC: \${$marketCapBillions}B)\n";
}

echo "\n=== VŠETKY TICKERS BEZ MARKET CAP ===\n";
$stmt = $pdo->query("SELECT ticker, current_price FROM TodayEarningsMovements WHERE market_cap IS NULL OR market_cap = 0 ORDER BY ticker");
$withoutMarketCap = $stmt->fetchAll();

echo "Tickers bez market cap: " . count($withoutMarketCap) . "\n";
foreach ($withoutMarketCap as $row) {
    echo "- {$row['ticker']}: \${$row['current_price']}\n";
}
?>
