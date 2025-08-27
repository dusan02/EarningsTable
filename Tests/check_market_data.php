<?php
require_once 'config.php';

echo "=== MARKET DATA CHECK ===\n\n";

// Check TodayEarningsMovements table
echo "TodayEarningsMovements data:\n";
echo "----------------------------\n";
$stmt = $pdo->query("SELECT ticker, current_price, market_cap, size, updated_at FROM TodayEarningsMovements LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-6s | Price: %-8s | Market Cap: %-12s | Size: %-8s | %s\n",
        $row['ticker'],
        $row['current_price'] ?: 'NULL',
        $row['market_cap'] ?: 'NULL',
        $row['size'] ?: 'NULL',
        $row['updated_at']
    );
}

echo "\n=== STATISTICS ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM TodayEarningsMovements");
$total = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as with_price FROM TodayEarningsMovements WHERE current_price > 0");
$withPrice = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as with_market_cap FROM TodayEarningsMovements WHERE market_cap > 0");
$withMarketCap = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as with_size FROM TodayEarningsMovements WHERE size IS NOT NULL AND size != 'Unknown'");
$withSize = $stmt->fetchColumn();

echo "Total records: {$total}\n";
echo "Records with price: {$withPrice}\n";
echo "Records with market cap: {$withMarketCap}\n";
echo "Records with size: {$withSize}\n";

echo "\n=== SAMPLE WITH DATA ===\n";
$stmt = $pdo->query("SELECT ticker, current_price, market_cap, size FROM TodayEarningsMovements WHERE current_price > 0 OR market_cap > 0 LIMIT 5");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-6s | Price: %-8s | Market Cap: %-12s | Size: %-8s\n",
        $row['ticker'],
        $row['current_price'] ?: 'NULL',
        $row['market_cap'] ?: 'NULL',
        $row['size'] ?: 'NULL'
    );
}
?>
