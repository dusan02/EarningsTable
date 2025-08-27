<?php
require_once 'config.php';

echo "=== TODAYEARNINGSMOVEMENTS TABLE SCHEMA ===\n";

$stmt = $pdo->query('DESCRIBE todayearningsmovements');
while ($row = $stmt->fetch()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Null'] . ' - ' . $row['Key'] . ' - ' . $row['Default'] . "\n";
}

echo "\n=== SAMPLE DATA ===\n";
$stmt = $pdo->query('SELECT * FROM todayearningsmovements LIMIT 3');
while ($row = $stmt->fetch()) {
    echo "Ticker: " . $row['ticker'] . "\n";
    echo "Company: " . $row['company_name'] . "\n";
    echo "Price: " . $row['current_price'] . "\n";
    echo "Market Cap: " . $row['market_cap'] . "\n";
    echo "Size: " . $row['size'] . "\n";
    echo "Updated: " . $row['updated_at'] . "\n";
    echo "---\n";
}
?>
