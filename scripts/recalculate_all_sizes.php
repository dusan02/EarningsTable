<?php
require_once 'D:/xampp/htdocs/earnings-table/config.php';
require_once __DIR__ . '/../common/error_handler.php';

echo "🔧 RECALCULATING ALL SIZE CATEGORIES\n";
echo "====================================\n\n";

// Get all records from database
$stmt = $pdo->query("SELECT ticker, market_cap, size FROM TodayEarningsMovements ORDER BY market_cap DESC");
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updatedCount = 0;
$errors = [];

foreach ($records as $record) {
    $ticker = $record['ticker'];
    $marketCap = $record['market_cap'];
    $currentSize = $record['size'];
    
    // Calculate correct size
    $correctSize = match (true) {
        $marketCap > 10000000000 => 'Large',
        $marketCap > 1000000000 => 'Mid',
        default => 'Small'
    };
    
    // Check if size needs updating
    if ($currentSize !== $correctSize) {
        echo "🔄 {$ticker}: {$currentSize} → {$correctSize} (MC: $" . number_format($marketCap) . ")\n";
        
        // Update database
        $updateStmt = $pdo->prepare("UPDATE TodayEarningsMovements SET size = ? WHERE ticker = ?");
        $result = $updateStmt->execute([$correctSize, $ticker]);
        
        if ($result) {
            $updatedCount++;
        } else {
            $errors[] = $ticker;
        }
    }
}

echo "\n📊 SUMMARY:\n";
echo "  ✅ Updated: {$updatedCount} tickers\n";
displayWarning("Errors: " . count($errors) . " tickers");

if (!empty($errors)) {
    logError("Failed to update size categories for tickers: " . implode(', ', $errors), [
        'tickers' => $errors,
        'operation' => 'recalculate_sizes'
    ]);
    displayWarning("Failed tickers: " . implode(', ', $errors));
}

// Show final distribution
echo "\n📈 FINAL SIZE DISTRIBUTION:\n";
$sizeStmt = $pdo->query("SELECT size, COUNT(*) as count FROM TodayEarningsMovements GROUP BY size ORDER BY size");
$sizes = $sizeStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($sizes as $size) {
    echo "  {$size['size']}: {$size['count']} tickers\n";
}
?> 