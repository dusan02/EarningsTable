<?php
require_once 'config.php';

echo "=== TICKERS IN DATABASE ===\n";
echo "Date: " . date('Y-m-d') . "\n\n";

$stmt = $pdo->query('SELECT ticker, current_price, price_change_percent FROM todayearningsmovements WHERE report_date = CURDATE() ORDER BY ticker');
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total tickers in database: " . count($results) . "\n\n";

foreach ($results as $row) {
    $price = $row['current_price'];
    $change = $row['price_change_percent'];
    
    if ($price == 0 || $price == null) {
        echo "❌ {$row['ticker']}: Price = $price, Change = $change%\n";
    } else {
        echo "✅ {$row['ticker']}: Price = $price, Change = $change%\n";
    }
}

echo "\n=== TICKERS FROM EARNINGS TABLE ===\n";
$stmt = $pdo->query('SELECT ticker FROM earningstickerstoday WHERE report_date = CURDATE() ORDER BY ticker');
$earningsTickers = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Earnings tickers: " . implode(', ', $earningsTickers) . "\n";
echo "Count: " . count($earningsTickers) . "\n";
?>
