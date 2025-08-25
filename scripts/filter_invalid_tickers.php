<?php
/**
 * Filter Invalid Tickers
 * Odstráni tickers bez dostupných údajov (ceny, market cap)
 */

require_once 'config.php';

echo "=== FILTER INVALID TICKERS ===\n\n";

// Get all tickers from TodayEarningsMovements
$stmt = $pdo->query("SELECT ticker, current_price, market_cap FROM TodayEarningsMovements ORDER BY ticker");
$allTickers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total tickers in database: " . count($allTickers) . "\n\n";

$validTickers = [];
$invalidTickers = [];

foreach ($allTickers as $ticker) {
    $hasPrice = !empty($ticker['current_price']) && $ticker['current_price'] > 0;
    $hasMarketCap = !empty($ticker['market_cap']) && $ticker['market_cap'] > 0;
    
    if ($hasPrice || $hasMarketCap) {
        $validTickers[] = $ticker['ticker'];
        echo "✅ {$ticker['ticker']}: Price=" . ($hasPrice ? "YES" : "NO") . ", MC=" . ($hasMarketCap ? "YES" : "NO") . "\n";
    } else {
        $invalidTickers[] = $ticker['ticker'];
        echo "❌ {$ticker['ticker']}: Price=" . ($hasPrice ? "YES" : "NO") . ", MC=" . ($hasMarketCap ? "YES" : "NO") . " (INVALID)\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Valid tickers: " . count($validTickers) . "\n";
echo "Invalid tickers: " . count($invalidTickers) . "\n";

if (!empty($invalidTickers)) {
    echo "\nInvalid tickers to remove:\n";
    foreach ($invalidTickers as $ticker) {
        echo "- {$ticker}\n";
    }
    
    echo "\nDo you want to remove invalid tickers? (y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim($line) === 'y' || trim($line) === 'Y') {
        echo "\nRemoving invalid tickers...\n";
        
        // Remove from TodayEarningsMovements
        $placeholders = str_repeat('?,', count($invalidTickers) - 1) . '?';
        $stmt = $pdo->prepare("DELETE FROM TodayEarningsMovements WHERE ticker IN ($placeholders)");
        $stmt->execute($invalidTickers);
        $deletedMovements = $stmt->rowCount();
        
        // Remove from EarningsTickersToday
        $stmt = $pdo->prepare("DELETE FROM EarningsTickersToday WHERE ticker IN ($placeholders)");
        $stmt->execute($invalidTickers);
        $deletedTickers = $stmt->rowCount();
        
        echo "✅ Removed {$deletedMovements} records from TodayEarningsMovements\n";
        echo "✅ Removed {$deletedTickers} records from EarningsTickersToday\n";
        
        // Show final count
        $stmt = $pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements");
        $finalCount = $stmt->fetchColumn();
        echo "\nFinal count: {$finalCount} valid tickers remaining\n";
        
    } else {
        echo "\nSkipping removal of invalid tickers.\n";
    }
} else {
    echo "\n✅ All tickers are valid!\n";
}

echo "\n✅ Filter completed\n";
?>
