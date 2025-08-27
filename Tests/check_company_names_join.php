<?php
require_once 'config.php';

$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "🔍 Checking company names join for date: {$date}\n\n";

// Check the same query as API
$stmt = $pdo->prepare("
    SELECT 
        e.ticker,
        COALESCE(m.company_name, e.ticker) as company_name,
        m.company_name as raw_company_name
    FROM EarningsTickersToday e
    LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
    WHERE e.report_date = ?
    ORDER BY e.ticker ASC
    LIMIT 10
");

$stmt->execute([$date]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📋 Sample results (first 10):\n";
foreach ($results as $row) {
    echo "  {$row['ticker']}: '{$row['company_name']}' (raw: '{$row['raw_company_name']}')\n";
}

// Count how many have proper company names
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN m.company_name IS NOT NULL AND m.company_name != e.ticker THEN 1 ELSE 0 END) as with_names,
        SUM(CASE WHEN m.company_name IS NULL OR m.company_name = e.ticker THEN 1 ELSE 0 END) as without_names
    FROM EarningsTickersToday e
    LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
    WHERE e.report_date = ?
");

$stmt->execute([$date]);
$counts = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\n📊 Summary:\n";
echo "  Total tickers: {$counts['total']}\n";
echo "  With company names: {$counts['with_names']}\n";
echo "  Without company names: {$counts['without_names']}\n";

// Check if TodayEarningsMovements has records for today's tickers
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count
    FROM TodayEarningsMovements m
    INNER JOIN EarningsTickersToday e ON m.ticker = e.ticker
    WHERE e.report_date = ?
");

$stmt->execute([$date]);
$joinCount = $stmt->fetch(PDO::FETCH_ASSOC);

echo "  Joined records: {$joinCount['count']}\n";
?>
