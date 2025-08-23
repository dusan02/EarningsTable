<?php
require_once 'D:/xampp/htdocs/config.php';

echo "=== SIMPLE API TEST ===\n\n";

// 1. Get today's tickers
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "1. Date: $date\n";

$stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
$stmt->execute([$date]);
$todayTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "2. Today's tickers count: " . count($todayTickers) . "\n";

// 2. Get market cap data for today's tickers
if (!empty($todayTickers)) {
    $placeholders = str_repeat('?,', count($todayTickers) - 1) . '?';
    $sql = "
        SELECT 
            ticker,
            market_cap,
            market_cap_diff,
            market_cap_diff_billions
        FROM TodayEarningsMovements 
        WHERE ticker IN ($placeholders)
    ";
    
    echo "3. SQL Query: $sql\n";
    echo "4. Parameters: " . implode(', ', $todayTickers) . "\n";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($todayTickers);
    $marketData = $stmt->fetchAll();
    
    echo "5. Market data count: " . count($marketData) . "\n";
    
    // Check HYPR and BABA
    foreach ($marketData as $row) {
        if ($row['ticker'] === 'HYPR' || $row['ticker'] === 'BABA') {
            echo "6. " . $row['ticker'] . ": market_cap_diff=" . ($row['market_cap_diff'] ?? 'NULL') . ", market_cap_diff_billions=" . ($row['market_cap_diff_billions'] ?? 'NULL') . "\n";
        }
    }
    
    // Count non-null market_cap_diff
    $withMarketCapDiff = 0;
    foreach ($marketData as $row) {
        if ($row['market_cap_diff'] !== null) {
            $withMarketCapDiff++;
        }
    }
    
    echo "7. Records with market_cap_diff: $withMarketCapDiff\n";
}
?>
