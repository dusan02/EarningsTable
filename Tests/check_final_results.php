<?php
require_once 'config.php';

echo "=== FINAL RESULTS CHECK ===\n\n";

// Get sample records with all data
$stmt = $pdo->prepare("
    SELECT ticker, company_name, current_price, market_cap, size, 
           price_change_percent, market_cap_diff_billions,
           eps_estimate, eps_actual, revenue_estimate, revenue_actual
    FROM TodayEarningsMovements 
    WHERE current_price > 0 AND market_cap > 0
    ORDER BY market_cap DESC
    LIMIT 10
");
$stmt->execute();
$sampleRecords = $stmt->fetchAll();

echo "Top 10 tickers by Market Cap:\n";
echo str_repeat("-", 80) . "\n";
foreach ($sampleRecords as $record) {
    $ticker = $record['ticker'];
    $companyName = $record['company_name'] ?? $ticker;
    $price = $record['current_price'];
    $marketCap = $record['market_cap'];
    $size = $record['size'];
    $priceChange = $record['price_change_percent'];
    $marketCapDiff = $record['market_cap_diff_billions'];
    
    echo sprintf("%-6s | %-30s | $%-8.2f | $%-6.1fB (%s) | %+.2f%% | $%.2fB\n",
        $ticker,
        substr($companyName, 0, 30),
        $price,
        $marketCap / 1000000000,
        $size,
        $priceChange ?? 0,
        $marketCapDiff ?? 0
    );
}

echo "\n" . str_repeat("-", 80) . "\n";

// Summary statistics
$stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE current_price > 0");
$stmt->execute();
$withPrice = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE market_cap > 0");
$stmt->execute();
$withMarketCap = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE eps_estimate IS NOT NULL");
$stmt->execute();
$withEPS = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE revenue_estimate IS NOT NULL");
$stmt->execute();
$withRevenue = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE size = 'Large'");
$stmt->execute();
$largeCap = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE size = 'Mid'");
$stmt->execute();
$midCap = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM TodayEarningsMovements WHERE size = 'Small'");
$stmt->execute();
$smallCap = $stmt->fetchColumn();

echo "SUMMARY STATISTICS:\n";
echo "Total records: 197\n";
echo "With prices: {$withPrice}\n";
echo "With market cap: {$withMarketCap}\n";
echo "With EPS estimates: {$withEPS}\n";
echo "With revenue estimates: {$withRevenue}\n";
echo "Large cap: {$largeCap}\n";
echo "Mid cap: {$midCap}\n";
echo "Small cap: {$smallCap}\n";

echo "\n✅ ALL DATA SUCCESSFULLY FETCHED!\n";
?>
