<?php
require_once __DIR__ . '/config.php';

echo "Testing complete data flow...\n\n";

// Get current date in US Eastern Time
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Date: $date\n\n";

// 1. Check if cron data exists
echo "=== 1. CRON → DATABASE ===\n";

// Check EarningsTickersToday (from fetch_earnings_tickers.php)
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM EarningsTickersToday WHERE report_date = ?");
$stmt->execute([$date]);
$earningsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "EarningsTickersToday records: $earningsCount\n";

// Check TodayEarningsMovements (from current_prices_mcaps_updates.php)
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements");
$stmt->execute();
$movementsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "TodayEarningsMovements records: $movementsCount\n";

// Check sample data from TodayEarningsMovements
$stmt = $pdo->query("
    SELECT ticker, market_cap, current_price, size 
    FROM TodayEarningsMovements 
    ORDER BY market_cap DESC 
    LIMIT 5
");
$sampleData = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nSample data from TodayEarningsMovements (Market Cap DESC):\n";
foreach ($sampleData as $row) {
    $marketCap = $row['market_cap'] ? number_format($row['market_cap'] / 1e9, 2) . 'B' : 'NULL';
    $price = $row['current_price'] ? '$' . number_format($row['current_price'], 2) : 'NULL';
    echo "  {$row['ticker']}: MC={$marketCap}, Price={$price}, Size={$row['size']}\n";
}

// 2. Check Database → API
echo "\n=== 2. DATABASE → API ===\n";

// Test the exact query from getEarningsDataWithMarketCap
require_once 'utils/database.php';
$earnings = getEarningsDataWithMarketCap($pdo, $date);

echo "API query returned: " . count($earnings) . " records\n";
echo "First 5 records from API:\n";
for ($i = 0; $i < 5; $i++) {
    $item = $earnings[$i];
    $marketCap = $item['market_cap'] ? number_format($item['market_cap'] / 1e9, 2) . 'B' : 'NULL';
    $price = $item['current_price'] ? '$' . number_format($item['current_price'], 2) : 'NULL';
    echo "  {$item['ticker']}: MC={$marketCap}, Price={$price}, Size={$item['size']}\n";
}

// 3. Check API → Frontend
echo "\n=== 3. API → FRONTEND ===\n";

// Simulate the exact API response that frontend receives
$response = [
    'date' => $date,
    'total' => count($earnings),
    'data' => $earnings
];

echo "API response structure:\n";
echo "  Date: {$response['date']}\n";
echo "  Total: {$response['total']}\n";
echo "  Data count: " . count($response['data']) . "\n";

// Check if data is properly ordered in API response
echo "\nFirst 5 records in API response:\n";
for ($i = 0; $i < 5; $i++) {
    $item = $response['data'][$i];
    $marketCap = $item['market_cap'] ? number_format($item['market_cap'] / 1e9, 2) . 'B' : 'NULL';
    $price = $item['current_price'] ? '$' . number_format($item['current_price'], 2) : 'NULL';
    echo "  {$item['ticker']}: MC={$marketCap}, Price={$price}\n";
}

// 4. Check Zoradenie
echo "\n=== 4. ZORADENIE ===\n";

// Check if the ORDER BY clause is working
$stmt = $pdo->prepare("
    SELECT 
        e.ticker,
        m.market_cap,
        m.current_price
    FROM EarningsTickersToday e
    LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
    WHERE e.report_date = ?
    ORDER BY COALESCE(m.market_cap, 0) DESC, e.ticker
    LIMIT 10
");
$stmt->execute([$date]);
$orderedData = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Database query with ORDER BY (first 10):\n";
foreach ($orderedData as $i => $row) {
    $marketCap = $row['market_cap'] ? number_format($row['market_cap'] / 1e9, 2) . 'B' : 'NULL';
    $price = $row['current_price'] ? '$' . number_format($row['current_price'], 2) : 'NULL';
    echo "  " . ($i + 1) . ". {$row['ticker']}: MC={$marketCap}, Price={$price}\n";
}

echo "\n=== SUMMARY ===\n";
echo "Data flow analysis complete.\n";
?>
