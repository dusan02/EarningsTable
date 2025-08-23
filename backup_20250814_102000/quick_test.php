<?php
require_once 'config.php';

$stmt = $pdo->query("SELECT ticker, company_name FROM TodayEarningsMovements WHERE company_name IS NOT NULL AND company_name != ticker LIMIT 5");
$results = $stmt->fetchAll();

echo "Sample company names:\n";
foreach($results as $row) {
    echo $row['ticker'] . ': ' . $row['company_name'] . "\n";
}
?>
