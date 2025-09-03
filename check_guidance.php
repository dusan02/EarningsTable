<?php
require_once 'config.php';

echo "Checking benzinga_guidance table...\n";
$stmt = $pdo->query('SELECT COUNT(*) as total FROM benzinga_guidance');
echo "Total records: " . $stmt->fetchColumn() . "\n";

echo "Checking for CRDO records...\n";
$stmt = $pdo->query('SELECT ticker, estimated_eps_guidance, estimated_revenue_guidance, fiscal_period, fiscal_year, updated_at FROM benzinga_guidance WHERE ticker = "CRDO"');
while($row = $stmt->fetch()) {
    echo "CRDO: " . json_encode($row) . "\n";
}

echo "Checking for any guidance records older than today...\n";
$today = date('Y-m-d');
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM benzinga_guidance WHERE DATE(updated_at) < ?');
$stmt->execute([$today]);
echo "Records older than today: " . $stmt->fetchColumn() . "\n";

echo "Sample of old records:\n";
$stmt = $pdo->prepare('SELECT ticker, estimated_eps_guidance, estimated_revenue_guidance, fiscal_period, fiscal_year, updated_at FROM benzinga_guidance WHERE DATE(updated_at) < ? LIMIT 5');
$stmt->execute([$today]);
while($row = $stmt->fetch()) {
    echo "Old record: " . json_encode($row) . "\n";
}
?>
