<?php
require_once __DIR__ . '/test_config.php';

echo "=== DATABASE STATUS ===\n";
echo "earningstickerstoday: " . $pdo->query('SELECT COUNT(*) FROM earningstickerstoday')->fetchColumn() . "\n";
echo "todayearningsmovements: " . $pdo->query('SELECT COUNT(*) FROM todayearningsmovements')->fetchColumn() . "\n";

echo "\nSample data from earningstickerstoday:\n";
$stmt = $pdo->query('SELECT ticker, report_date, report_time, eps_estimate FROM earningstickerstoday LIMIT 10');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['ticker'] . " - " . $row['report_date'] . " " . $row['report_time'] . " - EPS Est: " . $row['eps_estimate'] . "\n";
}

echo "\nSample data from todayearningsmovements:\n";
$stmt = $pdo->query('SELECT ticker, current_price, market_cap, eps_actual FROM todayearningsmovements LIMIT 10');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- " . $row['ticker'] . " - Price: $" . $row['current_price'] . ", Market Cap: $" . number_format($row['market_cap']) . ", EPS: " . $row['eps_actual'] . "\n";
}
?>
