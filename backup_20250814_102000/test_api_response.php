<?php
require_once __DIR__ . '/config.php';

echo "Testing API response structure...\n\n";

// Test earnings-tickers-today.php endpoint
echo "=== Testing earnings-tickers-today.php ===\n";

// Simulate the API call
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

require_once 'utils/database.php';
$earnings = getEarningsDataWithMarketCap($pdo, $date);

$response = [
    'date' => $date,
    'total' => count($earnings),
    'data' => $earnings
];

echo "Response structure:\n";
echo "Date: " . $response['date'] . "\n";
echo "Total: " . $response['total'] . "\n";
echo "Data count: " . count($response['data']) . "\n\n";

// Check first 5 records
echo "First 5 records (checking order):\n";
for ($i = 0; $i < 5; $i++) {
    $item = $response['data'][$i];
    echo sprintf("%d. %s: MC=%s, Price=%s\n", 
        $i + 1,
        $item['ticker'],
        $item['market_cap'] ? number_format($item['market_cap'] / 1e9, 2) . 'B' : 'NULL',
        $item['current_price'] ? '$' . number_format($item['current_price'], 2) : 'NULL'
    );
}

echo "\n=== Testing today-earnings-movements.php ===\n";

// Test the other endpoint
$stmt = $pdo->prepare("
    SELECT 
        e.ticker,
        COALESCE(m.company_name, e.ticker) as company_name,
        COALESCE(m.current_price, 0) as current_price,
        COALESCE(m.previous_close, 0) as previous_close,
        COALESCE(m.market_cap, 0) as market_cap,
        COALESCE(m.size, 'Unknown') as size,
        COALESCE(m.price_change_percent, 0) as price_change_percent,
        e.report_time,
        e.eps_actual,
        e.revenue_actual
    FROM EarningsTickersToday e
    LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
    WHERE e.report_date = ?
    ORDER BY m.market_cap DESC, e.ticker ASC
    LIMIT 5
");

$stmt->execute([$date]);
$data2 = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "First 5 records from alternative endpoint:\n";
foreach ($data2 as $i => $item) {
    echo sprintf("%d. %s: MC=%s, Price=%s\n", 
        $i + 1,
        $item['ticker'],
        $item['market_cap'] ? number_format($item['market_cap'] / 1e9, 2) . 'B' : 'NULL',
        $item['current_price'] ? '$' . number_format($item['current_price'], 2) : 'NULL'
    );
}

echo "\n";
?>
