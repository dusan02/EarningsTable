<?php
require_once 'config.php';

echo "🔍 Testing company names in database\n";

$stmt = $pdo->prepare("
    SELECT ticker, company_name 
    FROM TodayEarningsMovements 
    WHERE company_name IS NOT NULL AND company_name != ticker
    ORDER BY ticker 
    LIMIT 10
");

$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📋 Sample company names:\n";
foreach ($results as $row) {
    echo "  {$row['ticker']}: '{$row['company_name']}'\n";
}

echo "\n📊 Total records with company names: ";
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM TodayEarningsMovements 
    WHERE company_name IS NOT NULL AND company_name != ticker
");
$stmt->execute();
$count = $stmt->fetch()['count'];
echo $count . "\n";

echo "📊 Total records: ";
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM TodayEarningsMovements");
$stmt->execute();
$total = $stmt->fetch()['count'];
echo $total . "\n";
?>
