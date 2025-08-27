<?php
require_once __DIR__ . '/../config/config.php';

echo "=== API DATA CHECK ===\n\n";

// Get current date in US Eastern Time
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "Checking data for date: {$date}\n\n";

// Check EarningsTickersToday
echo "=== EarningsTickersToday ===\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM EarningsTickersToday WHERE report_date = ?");
$stmt->execute([$date]);
$earningsCount = $stmt->fetchColumn();
echo "Records in EarningsTickersToday: {$earningsCount}\n";

if ($earningsCount > 0) {
    $stmt = $pdo->prepare("SELECT ticker, eps_estimate, revenue_estimate FROM EarningsTickersToday WHERE report_date = ? LIMIT 5");
    $stmt->execute([$date]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-6s | EPS: %-8s | Revenue: %-12s\n",
            $row['ticker'],
            $row['eps_estimate'] ?: 'NULL',
            $row['revenue_estimate'] ?: 'NULL'
        );
    }
}

echo "\n=== TodayEarningsMovements ===\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements");
$stmt->execute();
$movementsCount = $stmt->fetchColumn();
echo "Records in TodayEarningsMovements: {$movementsCount}\n";

if ($movementsCount > 0) {
    $stmt = $pdo->prepare("SELECT ticker, current_price, market_cap, size FROM TodayEarningsMovements LIMIT 5");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-6s | Price: %-8s | Market Cap: %-12s | Size: %-8s\n",
            $row['ticker'],
            $row['current_price'] ?: 'NULL',
            $row['market_cap'] ?: 'NULL',
            $row['size'] ?: 'NULL'
        );
    }
}

echo "\n=== JOINED DATA (API QUERY) ===\n";
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
        e.eps_estimate,
        e.revenue_actual,
        e.revenue_estimate
    FROM EarningsTickersToday e
    LEFT JOIN TodayEarningsMovements m ON e.ticker = m.ticker
    WHERE e.report_date = ?
    ORDER BY m.market_cap DESC, e.ticker ASC
    LIMIT 5
");

$stmt->execute([$date]);
$joinedData = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Joined records: " . count($joinedData) . "\n";
foreach ($joinedData as $row) {
    echo sprintf("%-6s | Price: %-8s | Market Cap: %-12s | Size: %-8s | EPS: %-8s\n",
        $row['ticker'],
        $row['current_price'] ?: 'NULL',
        $row['market_cap'] ?: 'NULL',
        $row['size'] ?: 'NULL',
        $row['eps_estimate'] ?: 'NULL'
    );
}
?>
