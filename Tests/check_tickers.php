<?php
require_once '../config/config.php';

echo "=== DATABASE STATUS ===\n";
echo "EarningsTickersToday: " . $pdo->query('SELECT COUNT(*) FROM EarningsTickersToday')->fetchColumn() . "\n";
echo "TodayEarningsMovements: " . $pdo->query('SELECT COUNT(*) FROM TodayEarningsMovements')->fetchColumn() . "\n";

echo "\nSample data from EarningsTickersToday:\n";
$stmt = $pdo->query('SELECT ticker, report_date, report_time, eps_estimate FROM EarningsTickersToday LIMIT 10');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['ticker'] . " - " . $row['report_date'] . " " . $row['report_time'] . " - EPS Est: " . $row['eps_estimate'] . "\n";
}

echo "\nSample data from TodayEarningsMovements:\n";
$stmt = $pdo->query('SELECT ticker, current_price, market_cap, eps_actual FROM TodayEarningsMovements LIMIT 10');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['ticker'] . " - Price: $" . $row['current_price'] . ", Market Cap: $" . number_format($row['market_cap']) . ", EPS: " . $row['eps_actual'] . "\n";
}
?>
