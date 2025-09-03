<?php
require_once 'config.php';

echo "=== SIMULATING REAL DASHBOARD DATA ===\n\n";

// Simulate realistic price changes for testing
$priceChanges = [
    'AAPL' => 2.34,
    'MSFT' => -1.56,
    'CRM' => 4.67,
    'HPE' => -0.89,
    'CNM' => 1.23,
    'CRDO' => -2.45,
    'CXM' => 0.78,
    'GTLB' => 3.12,
    'REVG' => -1.34,
    'CPB' => 0.56
];

global $pdo;

echo "🔄 Updating price_change_percent and market_cap_diff for testing...\n\n";

foreach ($priceChanges as $ticker => $changePercent) {
    // Get current data
    $stmt = $pdo->prepare("SELECT market_cap, current_price, previous_close FROM TodayEarningsMovements WHERE ticker = ?");
    $stmt->execute([$ticker]);
    $data = $stmt->fetch();
    
    if ($data) {
        $marketCap = $data['market_cap'];
        $marketCapDiff = ($changePercent / 100) * $marketCap;
        $marketCapDiffBillions = $marketCapDiff / 1000000000;
        
        // Calculate new current_price based on change percent
        $previousClose = $data['previous_close'];
        $newCurrentPrice = $previousClose * (1 + $changePercent / 100);
        
        // Update database
        $updateStmt = $pdo->prepare("
            UPDATE TodayEarningsMovements 
            SET current_price = ?, 
                price_change_percent = ?, 
                market_cap_diff = ?, 
                market_cap_diff_billions = ?
            WHERE ticker = ?
        ");
        $updateStmt->execute([
            $newCurrentPrice,
            $changePercent,
            $marketCapDiff,
            $marketCapDiffBillions,
            $ticker
        ]);
        
        echo "✅ {$ticker}: Change {$changePercent}%, Market Cap Diff: " . number_format($marketCapDiffBillions, 2) . "B\n";
    }
}

echo "\n=== VERIFICATION ===\n";
$stmt = $pdo->prepare("
    SELECT ticker, price_change_percent, market_cap_diff_billions 
    FROM TodayEarningsMovements 
    WHERE price_change_percent != 0 
    ORDER BY ABS(price_change_percent) DESC
    LIMIT 10
");
$stmt->execute();
$results = $stmt->fetchAll();

foreach ($results as $row) {
    echo "Ticker: {$row['ticker']}, Change: {$row['price_change_percent']}%, Market Cap Diff: {$row['market_cap_diff_billions']}B\n";
}

echo "\n✅ Test data simulation completed!\n";
echo "Now the dashboard should show non-zero values in Change and Market Diff columns.\n";
?>
