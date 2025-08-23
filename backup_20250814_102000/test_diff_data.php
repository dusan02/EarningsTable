<?php
require_once 'config.php';

$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "📊 Checking market_cap_diff data for date: {$date}\n\n";

// Check if market_cap_diff data exists
$stmt = $pdo->prepare("
    SELECT 
        e.ticker,
        e.report_time,
        m.market_cap,
        m.market_cap_diff,
        m.market_cap_diff_billions,
        m.current_price,
        m.price_change_percent
    FROM EarningsTickersToday e
    LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
    WHERE e.report_date = ? 
    LIMIT 10
");

$stmt->execute([$date]);
$samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📋 Sample data with market_cap_diff:\n";
foreach ($samples as $sample) {
    echo "  {$sample['ticker']}:\n";
    echo "    Market Cap: '" . ($sample['market_cap'] ?? 'NULL') . "'\n";
    echo "    Market Cap Diff: '" . ($sample['market_cap_diff'] ?? 'NULL') . "'\n";
    echo "    Market Cap Diff (B): '" . ($sample['market_cap_diff_billions'] ?? 'NULL') . "'\n";
    echo "    Current Price: '" . ($sample['current_price'] ?? 'NULL') . "'\n";
    echo "    Price Change %: '" . ($sample['price_change_percent'] ?? 'NULL') . "'\n";
    echo "\n";
}

// Count records with market_cap_diff
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN m.market_cap_diff IS NOT NULL THEN 1 ELSE 0 END) as has_diff,
        SUM(CASE WHEN m.market_cap_diff IS NULL THEN 1 ELSE 0 END) as no_diff
    FROM EarningsTickersToday e
    LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
    WHERE e.report_date = ?
");

$stmt->execute([$date]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "📈 Market Cap Diff Data Analysis:\n";
echo "  Total records: {$result['total']}\n";
echo "  Records with market_cap_diff: {$result['has_diff']}\n";
echo "  Records without market_cap_diff: {$result['no_diff']}\n";
?>
