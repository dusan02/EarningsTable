<?php
require_once 'config.php';

echo "=== DATABASE STATUS ===\n";
echo "EarningsTickersToday: " . $pdo->query('SELECT COUNT(*) FROM EarningsTickersToday')->fetchColumn() . "\n";
echo "TodayEarningsMovements: " . $pdo->query('SELECT COUNT(*) FROM TodayEarningsMovements')->fetchColumn() . "\n";

if ($pdo->query('SELECT COUNT(*) FROM EarningsTickersToday')->fetchColumn() > 0) {
    echo "\nTickers found in EarningsTickersToday:\n";
    $stmt = $pdo->query('SELECT ticker, report_date, report_time FROM EarningsTickersToday');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['ticker'] . " (" . $row['report_date'] . " " . $row['report_time'] . ")\n";
    }
} else {
    echo "\nNo tickers found in EarningsTickersToday\n";
}

if ($pdo->query('SELECT COUNT(*) FROM TodayEarningsMovements')->fetchColumn() > 0) {
    echo "\nTickers found in TodayEarningsMovements:\n";
    $stmt = $pdo->query('SELECT ticker, current_price, market_cap FROM TodayEarningsMovements');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['ticker'] . " (Price: $" . $row['current_price'] . ", MC: $" . number_format($row['market_cap']) . ")\n";
    }
} else {
    echo "\nNo tickers found in TodayEarningsMovements\n";
}
?>
