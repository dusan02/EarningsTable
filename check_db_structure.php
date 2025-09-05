<?php
require_once 'config.php';

echo "=== DATABASE STRUCTURE ===\n";

// Check table structure
$stmt = $pdo->query('DESCRIBE todayearningsmovements');
echo "Columns in todayearningsmovements:\n";
while ($row = $stmt->fetch()) {
    echo "  - {$row['Field']} ({$row['Type']})\n";
}

echo "\n=== SAMPLE DATA ===\n";
$stmt = $pdo->prepare('SELECT * FROM todayearningsmovements WHERE report_date = ? LIMIT 5');
$stmt->execute([date('Y-m-d')]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    echo "Ticker: {$row['ticker']}\n";
    foreach ($row as $key => $value) {
        if (strpos($key, 'price') !== false || strpos($key, 'change') !== false) {
            echo "  $key: $value\n";
        }
    }
    echo "\n";
}
?>
