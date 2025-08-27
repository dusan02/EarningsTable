<?php
require_once 'config.php';

echo "=== ODSTRAŇOVANIE TESTOVACÍCH DÁT ===\n\n";

// Remove test data (big tech companies) from TodayEarningsMovements
$stmt = $pdo->prepare("DELETE FROM TodayEarningsMovements WHERE ticker IN (?, ?, ?, ?, ?)");
$result = $stmt->execute(['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA']);
$deletedMovements = $stmt->rowCount();

echo "Odstránené z TodayEarningsMovements: {$deletedMovements} záznamov\n";

// Remove test data from EarningsTickersToday
$stmt = $pdo->prepare("DELETE FROM EarningsTickersToday WHERE ticker IN (?, ?, ?, ?, ?)");
$result = $stmt->execute(['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'TSLA']);
$deletedTickers = $stmt->rowCount();

echo "Odstránené z EarningsTickersToday: {$deletedTickers} záznamov\n";

echo "\n=== KONTROLA PO ODSTRAŇOVANÍ ===\n";

// Check remaining data
$stmt = $pdo->query("SELECT COUNT(*) FROM TodayEarningsMovements");
$remainingMovements = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM EarningsTickersToday");
$remainingTickers = $stmt->fetchColumn();

echo "Zostávajúcich záznamov:\n";
echo "- TodayEarningsMovements: {$remainingMovements}\n";
echo "- EarningsTickersToday: {$remainingTickers}\n";

echo "\n=== ZOSTÁVAJÚCE TICKERS ===\n";
$stmt = $pdo->query("SELECT ticker, current_price, price_change_percent FROM TodayEarningsMovements ORDER BY ticker");
$remainingData = $stmt->fetchAll();

foreach ($remainingData as $row) {
    echo "- {$row['ticker']}: \${$row['current_price']} (Change: {$row['price_change_percent']}%)\n";
}

echo "\n✅ Testovacie dáta odstránené!\n";
?>
