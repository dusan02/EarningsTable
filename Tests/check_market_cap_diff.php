<?php
require_once 'config.php';

echo "=== MARKET CAP DIFF CHECK ===\n\n";

// Check TodayEarningsMovements table for market_cap_diff
echo "TodayEarningsMovements with market_cap_diff:\n";
echo "--------------------------------------------\n";
$stmt = $pdo->query("SELECT ticker, current_price, previous_close, price_change_percent, market_cap, market_cap_diff, market_cap_diff_billions FROM TodayEarningsMovements WHERE market_cap_diff IS NOT NULL AND market_cap_diff != 0 LIMIT 10");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-6s | Price: %-8s | Prev: %-8s | Change: %-8s | Market Cap: %-12s | Diff: %-12s | Diff B: %-8s\n",
        $row['ticker'],
        $row['current_price'] ?: 'NULL',
        $row['previous_close'] ?: 'NULL',
        $row['price_change_percent'] ?: 'NULL',
        $row['market_cap'] ?: 'NULL',
        $row['market_cap_diff'] ?: 'NULL',
        $row['market_cap_diff_billions'] ?: 'NULL'
    );
}

echo "\n=== STATISTICS ===\n";
$stmt = $pdo->query("SELECT COUNT(*) as total FROM TodayEarningsMovements");
$total = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as with_market_cap_diff FROM TodayEarningsMovements WHERE market_cap_diff IS NOT NULL AND market_cap_diff != 0");
$withMarketCapDiff = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as with_price_change FROM TodayEarningsMovements WHERE price_change_percent IS NOT NULL");
$withPriceChange = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) as with_market_cap FROM TodayEarningsMovements WHERE market_cap > 0");
$withMarketCap = $stmt->fetchColumn();

echo "Total records: {$total}\n";
echo "Records with market_cap_diff: {$withMarketCapDiff}\n";
echo "Records with price_change_percent: {$withPriceChange}\n";
echo "Records with market_cap: {$withMarketCap}\n";

echo "\n=== SAMPLE CALCULATION CHECK ===\n";
$stmt = $pdo->query("SELECT ticker, current_price, previous_close, price_change_percent, market_cap, market_cap_diff FROM TodayEarningsMovements WHERE market_cap_diff IS NOT NULL AND market_cap_diff != 0 LIMIT 3");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ticker = $row['ticker'];
    $currentPrice = $row['current_price'];
    $previousClose = $row['previous_close'];
    $priceChangePercent = $row['price_change_percent'];
    $marketCap = $row['market_cap'];
    $marketCapDiff = $row['market_cap_diff'];
    
    // Manual calculation
    $calculatedDiff = ($priceChangePercent / 100) * $marketCap;
    
    echo "{$ticker}:\n";
    echo "  Price change: {$priceChangePercent}%\n";
    echo "  Market cap: $" . number_format($marketCap / 1000000000, 1) . "B\n";
    echo "  Stored diff: $" . number_format($marketCapDiff / 1000000000, 1) . "B\n";
    echo "  Calculated diff: $" . number_format($calculatedDiff / 1000000000, 1) . "B\n";
    echo "  Match: " . (abs($marketCapDiff - $calculatedDiff) < 0.01 ? "✅" : "❌") . "\n\n";
}
?>
