<?php
require_once 'config.php';

echo "Checking CRDO earnings data...\n";
$stmt = $pdo->query('SELECT ticker, report_date, eps_estimate, revenue_estimate FROM EarningsTickersToday WHERE ticker = "CRDO"');
$row = $stmt->fetch();
echo "CRDO earnings: " . json_encode($row) . "\n";

echo "Checking CRDO guidance data...\n";
$stmt = $pdo->query('SELECT ticker, estimated_eps_guidance, estimated_revenue_guidance, fiscal_period, fiscal_year, updated_at FROM benzinga_guidance WHERE ticker = "CRDO"');
while($row = $stmt->fetch()) {
    echo "CRDO guidance: " . json_encode($row) . "\n";
}

echo "Checking if CRDO has earnings for today...\n";
$today = date('Y-m-d');
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM EarningsTickersToday WHERE ticker = "CRDO" AND report_date = ?');
$stmt->execute([$today]);
echo "CRDO earnings for today: " . $stmt->fetchColumn() . "\n";
?>
