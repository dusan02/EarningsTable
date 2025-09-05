<?php
require_once 'config.php';

echo "=== DATE CHECK ===\n";
echo "PHP Date: " . date('Y-m-d') . "\n";
echo "PHP DateTime: " . date('Y-m-d H:i:s') . "\n";

// Check what dates are in the database
$stmt = $pdo->query('SELECT DISTINCT report_date FROM todayearningsmovements ORDER BY report_date DESC LIMIT 5');
$dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "\nDates in todayearningsmovements:\n";
foreach ($dates as $date) {
    echo "  - $date\n";
}

// Check earnings table dates
$stmt = $pdo->query('SELECT DISTINCT report_date FROM earningstickerstoday ORDER BY report_date DESC LIMIT 5');
$dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "\nDates in earningstickerstoday:\n";
foreach ($dates as $date) {
    echo "  - $date\n";
}

// Check today's data specifically
echo "\n=== TODAY'S DATA ===\n";
$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM todayearningsmovements WHERE report_date = ?');
$stmt->execute([date('Y-m-d')]);
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "todayearningsmovements for today: $count records\n";

$stmt = $pdo->prepare('SELECT COUNT(*) as count FROM earningstickerstoday WHERE report_date = ?');
$stmt->execute([date('Y-m-d')]);
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "earningstickerstoday for today: $count records\n";

// Check all records regardless of date
$stmt = $pdo->query('SELECT COUNT(*) as count FROM todayearningsmovements');
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "Total todayearningsmovements: $count records\n";
?>
