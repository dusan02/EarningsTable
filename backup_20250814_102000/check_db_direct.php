<?php
require_once 'config.php';

$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "📊 Checking database directly for date: {$date}\n\n";

// Check first 5 records
$stmt = $pdo->prepare("
    SELECT ticker, eps_actual, eps_estimate, revenue_actual, revenue_estimate
    FROM EarningsTickersToday 
    WHERE report_date = ? 
    LIMIT 5
");

$stmt->execute([$date]);
$samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📋 Sample database records:\n";
foreach ($samples as $sample) {
    echo "  {$sample['ticker']}:\n";
    echo "    EPS Actual: '" . ($sample['eps_actual'] ?? 'NULL') . "'\n";
    echo "    EPS Estimate: '" . ($sample['eps_estimate'] ?? 'NULL') . "'\n";
    echo "    Revenue Actual: '" . ($sample['revenue_actual'] ?? 'NULL') . "'\n";
    echo "    Revenue Estimate: '" . ($sample['revenue_estimate'] ?? 'NULL') . "'\n";
    echo "\n";
}

// Count different value types
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN eps_actual IS NULL THEN 1 ELSE 0 END) as eps_actual_null,
        SUM(CASE WHEN eps_actual = '' THEN 1 ELSE 0 END) as eps_actual_empty,
        SUM(CASE WHEN eps_actual = '/' THEN 1 ELSE 0 END) as eps_actual_slash,
        SUM(CASE WHEN eps_actual IS NOT NULL AND eps_actual != '' AND eps_actual != '/' THEN 1 ELSE 0 END) as eps_actual_valid,
        SUM(CASE WHEN eps_estimate IS NULL THEN 1 ELSE 0 END) as eps_estimate_null,
        SUM(CASE WHEN eps_estimate = '' THEN 1 ELSE 0 END) as eps_estimate_empty,
        SUM(CASE WHEN eps_estimate = '/' THEN 1 ELSE 0 END) as eps_estimate_slash,
        SUM(CASE WHEN eps_estimate IS NOT NULL AND eps_estimate != '' AND eps_estimate != '/' THEN 1 ELSE 0 END) as eps_estimate_valid
    FROM EarningsTickersToday 
    WHERE report_date = ?
");

$stmt->execute([$date]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "📈 Database Value Analysis:\n";
echo "  Total records: {$result['total']}\n";
echo "  EPS Actual - NULL: {$result['eps_actual_null']}, Empty: {$result['eps_actual_empty']}, Slash: {$result['eps_actual_slash']}, Valid: {$result['eps_actual_valid']}\n";
echo "  EPS Estimate - NULL: {$result['eps_estimate_null']}, Empty: {$result['eps_estimate_empty']}, Slash: {$result['eps_estimate_slash']}, Valid: {$result['eps_estimate_valid']}\n";
?>
