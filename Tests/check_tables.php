<?php
require_once 'config.php';

echo "=== CHECKING TABLES ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

// Check EarningsTickersToday
$stmt = $pdo->prepare("SELECT COUNT(*) FROM EarningsTickersToday WHERE report_date = ?");
$stmt->execute([$date]);
$earningsCount = $stmt->fetchColumn();

// Check TodayEarningsMovements
$stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements");
$stmt->execute();
$movementsCount = $stmt->fetchColumn();

echo "EarningsTickersToday (today): $earningsCount\n";
echo "TodayEarningsMovements (total): $movementsCount\n";

// Check which tickers are missing from TodayEarningsMovements
$stmt = $pdo->prepare("
    SELECT e.ticker 
    FROM EarningsTickersToday e 
    LEFT JOIN TodayEarningsMovements t ON e.ticker = t.ticker 
    WHERE e.report_date = ? AND t.ticker IS NULL
");
$stmt->execute([$date]);
$missingTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "\nMissing tickers from TodayEarningsMovements: " . count($missingTickers) . "\n";
if (!empty($missingTickers)) {
    echo "Missing: " . implode(', ', array_slice($missingTickers, 0, 10));
    if (count($missingTickers) > 10) {
        echo " ... and " . (count($missingTickers) - 10) . " more";
    }
    echo "\n";
}

// Check which tickers have data in TodayEarningsMovements
$stmt = $pdo->prepare("
    SELECT t.ticker, t.current_price, t.market_cap 
    FROM TodayEarningsMovements t
    INNER JOIN EarningsTickersToday e ON t.ticker = e.ticker AND e.report_date = ?
    WHERE t.current_price IS NULL OR t.market_cap IS NULL
    LIMIT 5
");
$stmt->execute([$date]);
$nullData = $stmt->fetchAll();

echo "\nTickers with NULL data in TodayEarningsMovements:\n";
foreach ($nullData as $row) {
    echo sprintf("%-6s | Price: %s | MC: %s\n",
        $row['ticker'],
        $row['current_price'] ?? 'NULL',
        $row['market_cap'] ?? 'NULL'
    );
}
?>
