<?php
require_once 'config.php';

echo "=== TABLE RELATIONSHIPS ANALYSIS ===\n";
echo "Date: " . date('Y-m-d') . "\n\n";

// 1. Check table schemas
echo "=== 1. TABLE SCHEMAS ===\n";

echo "EARNINGSTICKERSTODAY columns:\n";
$stmt = $pdo->query('DESCRIBE earningstickerstoday');
while ($row = $stmt->fetch()) {
    echo "  " . $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\nTODAYEARNINGSMOVEMENTS columns:\n";
$stmt = $pdo->query('DESCRIBE todayearningsmovements');
while ($row = $stmt->fetch()) {
    echo "  " . $row['Field'] . " - " . $row['Type'] . "\n";
}

// 2. Check data counts
echo "\n=== 2. DATA COUNTS ===\n";

$stmt = $pdo->query('SELECT COUNT(*) as total FROM earningstickerstoday WHERE report_date = CURDATE()');
$ettCount = $stmt->fetch()['total'];

$stmt = $pdo->query('SELECT COUNT(*) as total FROM todayearningsmovements WHERE DATE(updated_at) = CURDATE()');
$temCount = $stmt->fetch()['total'];

echo "EarningsTickersToday (today): {$ettCount} records\n";
echo "TodayEarningsMovements (today): {$temCount} records\n";

// 3. Check overlapping tickers
echo "\n=== 3. OVERLAPPING TICKERS ===\n";

$stmt = $pdo->query("
    SELECT COUNT(*) as overlap
    FROM earningstickerstoday e
    INNER JOIN todayearningsmovements t ON e.ticker = t.ticker
    WHERE e.report_date = CURDATE() AND DATE(t.updated_at) = CURDATE()
");
$overlap = $stmt->fetch()['overlap'];

echo "Tickers in BOTH tables (today): {$overlap}\n";

// 4. Check tickers only in EarningsTickersToday
echo "\n=== 4. TICKERS ONLY IN EARNINGSTICKERSTODAY ===\n";

$stmt = $pdo->query("
    SELECT e.ticker, e.eps_estimate, e.revenue_estimate
    FROM earningstickerstoday e
    LEFT JOIN todayearningsmovements t ON e.ticker = t.ticker AND DATE(t.updated_at) = CURDATE()
    WHERE e.report_date = CURDATE() AND t.ticker IS NULL
    LIMIT 10
");

$onlyInEtt = $stmt->fetchAll();
echo "Tickers only in EarningsTickersToday: " . count($onlyInEtt) . "\n";
foreach ($onlyInEtt as $row) {
    echo "  {$row['ticker']}: EPS={$row['eps_estimate']}, Revenue={$row['revenue_estimate']}\n";
}

// 5. Check tickers only in TodayEarningsMovements
echo "\n=== 5. TICKERS ONLY IN TODAYEARNINGSMOVEMENTS ===\n";

$stmt = $pdo->query("
    SELECT t.ticker, t.current_price, t.market_cap
    FROM todayearningsmovements t
    LEFT JOIN earningstickerstoday e ON t.ticker = e.ticker AND e.report_date = CURDATE()
    WHERE DATE(t.updated_at) = CURDATE() AND e.ticker IS NULL
    LIMIT 10
");

$onlyInTem = $stmt->fetchAll();
echo "Tickers only in TodayEarningsMovements: " . count($onlyInTem) . "\n";
foreach ($onlyInTem as $row) {
    echo "  {$row['ticker']}: Price={$row['current_price']}, Market Cap={$row['market_cap']}\n";
}

// 6. Check complete records (in both tables)
echo "\n=== 6. COMPLETE RECORDS (BOTH TABLES) ===\n";

$stmt = $pdo->query("
    SELECT 
        e.ticker,
        e.eps_estimate,
        e.revenue_estimate,
        t.current_price,
        t.market_cap,
        t.size
    FROM earningstickerstoday e
    INNER JOIN todayearningsmovements t ON e.ticker = t.ticker
    WHERE e.report_date = CURDATE() AND DATE(t.updated_at) = CURDATE()
    LIMIT 10
");

$complete = $stmt->fetchAll();
echo "Complete records (both tables): " . count($complete) . "\n";
foreach ($complete as $row) {
    echo "  {$row['ticker']}: EPS={$row['eps_estimate']}, Price={$row['current_price']}, Size={$row['size']}\n";
}

// 7. Data source analysis
echo "\n=== 7. DATA SOURCE ANALYSIS ===\n";

$stmt = $pdo->query("
    SELECT data_source, COUNT(*) as count
    FROM earningstickerstoday
    WHERE report_date = CURDATE()
    GROUP BY data_source
");

$sources = $stmt->fetchAll();
foreach ($sources as $source) {
    echo "EarningsTickersToday - {$source['data_source']}: {$source['count']} records\n";
}

echo "\n=== 8. RELATIONSHIP SUMMARY ===\n";
echo "Primary Key Relationship: ticker (string)\n";
echo "EarningsTickersToday: Contains EPS/Revenue estimates from Finnhub\n";
echo "TodayEarningsMovements: Contains market data from Polygon\n";
echo "JOIN Condition: e.ticker = t.ticker\n";
echo "Date Filter: e.report_date = CURDATE() AND DATE(t.updated_at) = CURDATE()\n";
?>
