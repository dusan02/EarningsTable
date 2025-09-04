<?php
require_once 'config.php';

echo "=== EarningsTickersToday Table Structure ===\n";
$stmt = $pdo->query('DESCRIBE earningstickerstoday');
while($row = $stmt->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== Benzinga Guidance Table Structure ===\n";
$stmt = $pdo->query('DESCRIBE benzinga_guidance');
while($row = $stmt->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== Sample Guidance Data ===\n";
$stmt = $pdo->query('SELECT ticker, fiscal_period, fiscal_year, estimated_eps_guidance, estimated_revenue_guidance FROM benzinga_guidance LIMIT 3');
while($row = $stmt->fetch()) {
    echo "Ticker: {$row['ticker']}, Period: {$row['fiscal_period']}, Year: {$row['fiscal_year']}, EPS: {$row['estimated_eps_guidance']}, Rev: {$row['estimated_revenue_guidance']}\n";
}
?>
