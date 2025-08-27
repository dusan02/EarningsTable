<?php
require_once 'config.php';

echo "=== CHECKING MISSING TICKERS ===\n";

// Get today's date
$timezone = new DateTimeZone('America/New_York');
$usDate = new DateTime('now', $timezone);
$date = $usDate->format('Y-m-d');

// Get all today's tickers
$stmt = $pdo->prepare("SELECT ticker FROM EarningsTickersToday WHERE report_date = ?");
$stmt->execute([$date]);
$allTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get tickers with prices
$stmt = $pdo->prepare("SELECT ticker FROM TodayEarningsMovements WHERE current_price > 0");
$stmt->execute();
$tickersWithPrices = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get tickers with market cap
$stmt = $pdo->prepare("SELECT ticker FROM TodayEarningsMovements WHERE market_cap > 0");
$stmt->execute();
$tickersWithMarketCap = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Total tickers today: " . count($allTickers) . "\n";
echo "Tickers with prices: " . count($tickersWithPrices) . "\n";
echo "Tickers with market cap: " . count($tickersWithMarketCap) . "\n";

// Find missing tickers
$missingPrices = array_diff($allTickers, $tickersWithPrices);
$missingMarketCap = array_diff($allTickers, $tickersWithMarketCap);

echo "\nTickers missing prices (" . count($missingPrices) . "):\n";
foreach ($missingPrices as $ticker) {
    echo "  $ticker\n";
}

echo "\nTickers missing market cap (" . count($missingMarketCap) . "):\n";
foreach ($missingMarketCap as $ticker) {
    echo "  $ticker\n";
}

// Check if these tickers exist in TodayEarningsMovements at all
echo "\n=== CHECKING TODAYEARNINGSMOVEMENTS ===\n";
foreach ($missingPrices as $ticker) {
    $stmt = $pdo->prepare("SELECT ticker, current_price, market_cap FROM TodayEarningsMovements WHERE ticker = ?");
    $stmt->execute([$ticker]);
    $row = $stmt->fetch();
    
    if ($row) {
        echo sprintf("%-6s | Price: %s | MC: %s\n",
            $ticker,
            $row['current_price'] ?? 'NULL',
            $row['market_cap'] ?? 'NULL'
        );
    } else {
        echo sprintf("%-6s | NOT FOUND in TodayEarningsMovements\n", $ticker);
    }
}
?>
