<?php
require_once 'config.php';

echo "=== MARKET CAP DATA CHECK ===\n";

// Check today's records with market cap
$stmt = $pdo->query("
    SELECT ticker, current_price, market_cap, size, company_name
    FROM todayearningsmovements 
    WHERE DATE(updated_at) = CURDATE() AND market_cap > 0
    ORDER BY market_cap DESC
    LIMIT 10
");

echo "Top 10 tickers by market cap (today):\n";
while ($row = $stmt->fetch()) {
    $marketCapB = round($row['market_cap'] / 1000000000, 2);
    echo "  {$row['ticker']}: \${$marketCapB}B ({$row['size']}) - {$row['company_name']}\n";
}

echo "\n=== SIZE BREAKDOWN ===\n";
$stmt = $pdo->query("
    SELECT size, COUNT(*) as count
    FROM todayearningsmovements 
    WHERE DATE(updated_at) = CURDATE() AND market_cap > 0
    GROUP BY size
    ORDER BY count DESC
");

while ($row = $stmt->fetch()) {
    echo "  {$row['size']}: {$row['count']} tickers\n";
}

echo "\n=== TOTAL RECORDS ===\n";
$stmt = $pdo->query("
    SELECT COUNT(*) as total
    FROM todayearningsmovements 
    WHERE DATE(updated_at) = CURDATE()
");
$total = $stmt->fetch()['total'];

$stmt = $pdo->query("
    SELECT COUNT(*) as with_mc
    FROM todayearningsmovements 
    WHERE DATE(updated_at) = CURDATE() AND market_cap > 0
");
$withMc = $stmt->fetch()['with_mc'];

echo "Total today: {$total}\n";
echo "With market cap: {$withMc}\n";
echo "Without market cap: " . ($total - $withMc) . "\n";
?>
