<?php
require_once 'config.php';

echo "=== API DEBUG TEST ===\n\n";

// 1. Get today's tickers
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

echo "1. Date: $date\n";

$stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
$stmt->execute([$date]);
$todayTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "2. Today's tickers count: " . count($todayTickers) . "\n";

// Check if HYPR and BABA are in today's tickers
$hyprInToday = in_array('HYPR', $todayTickers);
$babaInToday = in_array('BABA', $todayTickers);

echo "3. HYPR in today's tickers: " . ($hyprInToday ? 'YES' : 'NO') . "\n";
echo "4. BABA in today's tickers: " . ($babaInToday ? 'YES' : 'NO') . "\n";

// 2. Get market cap data for today's tickers
if (!empty($todayTickers)) {
    $placeholders = str_repeat('?,', count($todayTickers) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT 
            ticker,
            market_cap,
            market_cap_diff,
            market_cap_diff_billions
        FROM TodayEarningsMovements 
        WHERE ticker IN ($placeholders)
    ");
    $stmt->execute($todayTickers);
    $marketData = $stmt->fetchAll();
    
    echo "5. Market data count: " . count($marketData) . "\n";
    
    // Check HYPR and BABA in market data
    $hyprData = null;
    $babaData = null;
    
    foreach ($marketData as $row) {
        if ($row['ticker'] === 'HYPR') {
            $hyprData = $row;
        }
        if ($row['ticker'] === 'BABA') {
            $babaData = $row;
        }
    }
    
    echo "6. HYPR in market data: " . ($hyprData ? 'YES' : 'NO') . "\n";
    if ($hyprData) {
        echo "   HYPR market_cap_diff: " . $hyprData['market_cap_diff'] . "\n";
        echo "   HYPR market_cap_diff_billions: " . $hyprData['market_cap_diff_billions'] . "\n";
    }
    
    echo "7. BABA in market data: " . ($babaData ? 'YES' : 'NO') . "\n";
    if ($babaData) {
        echo "   BABA market_cap_diff: " . $babaData['market_cap_diff'] . "\n";
        echo "   BABA market_cap_diff_billions: " . $babaData['market_cap_diff_billions'] . "\n";
    }
    
    // Count non-null market_cap_diff
    $withMarketCapDiff = 0;
    foreach ($marketData as $row) {
        if ($row['market_cap_diff'] !== null) {
            $withMarketCapDiff++;
        }
    }
    
    echo "8. Records with market_cap_diff: $withMarketCapDiff\n";
    
    // Show top 5 by market_cap_diff_billions
    echo "9. Top 5 by market_cap_diff_billions:\n";
    usort($marketData, function($a, $b) {
        $aVal = $a['market_cap_diff_billions'] ?? 0;
        $bVal = $b['market_cap_diff_billions'] ?? 0;
        return $bVal <=> $aVal;
    });
    
    for ($i = 0; $i < min(5, count($marketData)); $i++) {
        $row = $marketData[$i];
        echo "   " . ($i + 1) . ". " . $row['ticker'] . ": " . ($row['market_cap_diff_billions'] ?? 'NULL') . "\n";
    }
}
?>
