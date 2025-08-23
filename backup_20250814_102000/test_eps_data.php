<?php
require_once 'config.php';

$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "📊 Checking EPS/Revenue data for date: {$date}\n";

$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_count,
        COUNT(eps_actual) as eps_actual_count,
        COUNT(eps_estimate) as eps_estimate_count,
        COUNT(revenue_actual) as revenue_actual_count,
        COUNT(revenue_estimate) as revenue_estimate_count
    FROM EarningsTickersToday 
    WHERE report_date = ?
");

$stmt->execute([$date]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "📈 Database Summary:\n";
echo "  Total tickers: {$result['total_count']}\n";
echo "  EPS Actual: {$result['eps_actual_count']}\n";
echo "  EPS Estimate: {$result['eps_estimate_count']}\n";
echo "  Revenue Actual: {$result['revenue_actual_count']}\n";
echo "  Revenue Estimate: {$result['revenue_estimate_count']}\n";

// Check a few sample records
$stmt = $pdo->prepare("
    SELECT ticker, eps_actual, eps_estimate, revenue_actual, revenue_estimate
    FROM EarningsTickersToday 
    WHERE report_date = ? 
    LIMIT 5
");

$stmt->execute([$date]);
$samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\n📋 Sample records:\n";
foreach ($samples as $sample) {
    echo "  {$sample['ticker']}: EPS={$sample['eps_actual']}/{$sample['eps_estimate']}, Revenue={$sample['revenue_actual']}/{$sample['revenue_estimate']}\n";
}
?>
